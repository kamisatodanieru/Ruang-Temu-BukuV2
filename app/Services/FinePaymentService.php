<?php

namespace App\Services;

use App\Enums\FineStatus;
use App\Enums\PaymentStatus;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class FinePaymentService
{
    public function __construct(
        protected MidtransService $midtransService
    ) {}

    /**
     * Pastikan tagihan denda dibuat atau diperbarui untuk pinjaman yang sedang overdue.
     */
    public function ensureFineForLoan(Loan $loan): ?Fine
    {
        if (! $loan->isOverdue()) {
            return $loan->fine;
        }

        $overdueDays = $loan->overdueDays();
        $finePerDay = (int) config('library.fine_per_day', 1000);
        $totalAmount = $overdueDays * $finePerDay;

        if ($totalAmount <= 0) {
            return $loan->fine;
        }

        $fine = Fine::query()->firstOrCreate(
            ['loan_id' => $loan->id],
            [
                'member_id' => $loan->member_id,
                'overdue_days' => $overdueDays,
                'fine_per_day' => $finePerDay,
                'total_amount' => $totalAmount,
                'status' => FineStatus::Unpaid,
            ]
        );

        if ($fine->isPayable() && ($fine->overdue_days !== $overdueDays || $fine->total_amount !== $totalAmount)) {
            $fine->overdue_days = $overdueDays;
            $fine->fine_per_day = $finePerDay;
            $fine->total_amount = $totalAmount;

            if ($fine->status === FineStatus::PendingPayment) {
                $fine->status = FineStatus::Unpaid;
                $fine->payments()
                    ->where('status', PaymentStatus::Pending->value)
                    ->update(['status' => PaymentStatus::Cancelled->value]);
            }

            $fine->save();
        }

        return $fine;
    }

    /**
     * Sinkronisasi status pembayaran langsung ke Midtrans API (berguna saat di localhost tanpa webhook publik).
     * Dilindungi cooldown singkat berbasis cache agar request GET berulang tidak memicu amplifikasi panggilan API.
     */
    public function syncPaymentStatusFromMidtrans(Fine $fine): void
    {
        if ($fine->status !== FineStatus::PendingPayment) {
            return;
        }

        $latestPayment = $fine->latestPayment;
        if (! $latestPayment || ! $latestPayment->order_id) {
            return;
        }

        $cooldownKey = "fine_midtrans_sync:{$fine->id}:{$latestPayment->order_id}";
        if (! Cache::add($cooldownKey, true, 15)) {
            return;
        }

        try {
            $statusResponse = (array) $this->midtransService->getTransactionStatus($latestPayment->order_id);
            if (! empty($statusResponse['transaction_status'])) {
                $statusResponse['order_id'] = $latestPayment->order_id;
                $this->handleWebhookNotification($statusResponse);
            }
        } catch (Throwable) {
            // Abaikan jika transaksi belum tercatat di Midtrans (misal user baru membuka popup tapi belum memilih metode bayar)
        }
    }

    /**
     * Buat transaksi pembayaran Midtrans Snap untuk tagihan denda.
     *
     * @throws ValidationException
     */
    public function initiatePayment(Fine $fine, User $actor, ?Request $request = null): Payment
    {
        return DB::transaction(function () use ($fine, $actor, $request): Payment {
            $lockedFine = Fine::query()
                ->with(['loan.book', 'member', 'latestPayment'])
                ->whereKey($fine->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedFine->member_id !== $actor->id) {
                abort(403, 'Anda tidak berwenang membayar tagihan denda milik anggota lain.');
            }

            if (! $lockedFine->isPayable()) {
                throw ValidationException::withMessages([
                    'fine' => 'Tagihan denda ini sudah lunas atau dibebaskan.',
                ]);
            }

            // Jika pinjaman masih overdue saat pembayaran dimulai, pastikan nominal denda dihitung ulang secara otoritatif
            if ($lockedFine->loan !== null && $lockedFine->loan->isOverdue()) {
                $currentOverdueDays = $lockedFine->loan->overdueDays();
                $finePerDay = (int) config('library.fine_per_day', 1000);
                $currentTotal = $currentOverdueDays * $finePerDay;

                if ($currentTotal > 0 && ($lockedFine->overdue_days !== $currentOverdueDays || $lockedFine->total_amount !== $currentTotal)) {
                    $lockedFine->overdue_days = $currentOverdueDays;
                    $lockedFine->fine_per_day = $finePerDay;
                    $lockedFine->total_amount = $currentTotal;
                    $lockedFine->save();
                }
            }

            // Gunakan kembali pembayaran Pending yang masih aktif jika nominalnya belum berubah (mencegah duplikasi transaksi/tagihan ganda)
            $existingPending = $lockedFine->latestPayment;
            if (
                $existingPending !== null
                && $existingPending->status === PaymentStatus::Pending
                && ! empty($existingPending->snap_token)
                && (int) $existingPending->gross_amount === (int) $lockedFine->total_amount
                && $existingPending->created_at !== null
                && $existingPending->created_at->greaterThan(now()->subHours(23))
            ) {
                return $existingPending;
            }

            // Batalkan transaksi pending lama pada denda ini sebelum menerbitkan Snap token baru
            $lockedFine->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->update(['status' => PaymentStatus::Cancelled->value]);

            $orderId = 'FINE-'.$lockedFine->id.'-'.time().'-'.Str::upper(Str::random(4));
            $bookTitle = $lockedFine->loan?->book?->title ?? 'Buku #'.$lockedFine->loan_id;

            $params = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => (int) $lockedFine->total_amount,
                ],
                'item_details' => [
                    [
                        'id' => 'FINE-'.$lockedFine->id,
                        'price' => (int) $lockedFine->fine_per_day,
                        'quantity' => (int) $lockedFine->overdue_days,
                        'name' => Str::limit("Denda Terlambat ({$lockedFine->overdue_days} hr): {$bookTitle}", 50, ''),
                    ],
                ],
                'customer_details' => [
                    'first_name' => $lockedFine->member->name,
                    'email' => $lockedFine->member->email,
                ],
            ];

            $snapToken = $this->midtransService->createSnapToken($params);

            $payment = Payment::query()->create([
                'order_id' => $orderId,
                'fine_id' => $lockedFine->id,
                'user_id' => $actor->id,
                'gross_amount' => $lockedFine->total_amount,
                'snap_token' => $snapToken,
                'status' => PaymentStatus::Pending,
            ]);

            $lockedFine->status = FineStatus::PendingPayment;
            $lockedFine->save();

            ActivityLogger::record(
                action: 'fine.payment_initiated',
                description: "Memulai pembayaran denda #{$lockedFine->id} dengan Order ID {$orderId} sebesar {$lockedFine->formattedAmount()}",
                subject: $lockedFine,
                actor: $actor,
                request: $request,
            );

            return $payment;
        });
    }

    /**
     * Proses payload webhook notifikasi dari Midtrans dan perbarui status Payment serta Fine.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhookNotification(array $payload): ?Payment
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        if ($orderId === '') {
            return null;
        }

        return DB::transaction(function () use ($orderId, $payload): ?Payment {
            $payment = Payment::query()
                ->where('order_id', $orderId)
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                return null;
            }

            $fine = Fine::query()
                ->whereKey($payment->fine_id)
                ->lockForUpdate()
                ->first();

            $transactionStatus = (string) ($payload['transaction_status'] ?? '');
            $fraudStatus = (string) ($payload['fraud_status'] ?? '');
            $paymentType = isset($payload['payment_type']) ? (string) $payload['payment_type'] : $payment->payment_type;
            $transactionId = isset($payload['transaction_id']) ? (string) $payload['transaction_id'] : $payment->transaction_id;

            $payment->payment_type = $paymentType;
            $payment->transaction_id = $transactionId;
            $payment->payment_details = $payload;

            $isSettled = ($transactionStatus === 'settlement')
                || ($transactionStatus === 'capture' && $fraudStatus === 'accept');

            if ($isSettled) {
                if (isset($payload['gross_amount']) && (int) round((float) $payload['gross_amount']) < (int) $payment->gross_amount) {
                    return $payment;
                }

                $wasAlreadyPaid = $payment->status === PaymentStatus::Paid;

                $payment->status = PaymentStatus::Paid;
                $payment->settled_at = $payment->settled_at ?? now();
                $payment->save();

                if (
                    $fine !== null
                    && $fine->status !== FineStatus::Paid
                    && $fine->status !== FineStatus::Waived
                    && (int) $payment->gross_amount >= (int) $fine->total_amount
                ) {
                    $fine->status = FineStatus::Paid;
                    $fine->paid_at = $fine->paid_at ?? now();
                    $fine->save();

                    if (! $wasAlreadyPaid) {
                        ActivityLogger::record(
                            action: 'fine.paid',
                            description: "Pembayaran denda #{$fine->id} lunas via Midtrans ({$paymentType}) dengan Order ID {$orderId}",
                            subject: $fine,
                            actor: $payment->user,
                        );
                    }
                }
            } elseif ($transactionStatus === 'pending') {
                if ($payment->status !== PaymentStatus::Paid) {
                    $payment->status = PaymentStatus::Pending;
                    $payment->save();
                }
            } elseif (in_array($transactionStatus, ['expire', 'cancel', 'deny'], true)) {
                if ($payment->status !== PaymentStatus::Paid) {
                    $payment->status = match ($transactionStatus) {
                        'expire' => PaymentStatus::Expired,
                        'cancel' => PaymentStatus::Cancelled,
                        default => PaymentStatus::Failed,
                    };
                    $payment->save();

                    if (
                        $fine !== null
                        && $fine->status !== FineStatus::Paid
                        && $fine->status !== FineStatus::Waived
                        && ! $fine->payments()->where('status', PaymentStatus::Pending->value)->exists()
                    ) {
                        $fine->status = FineStatus::Unpaid;
                        $fine->save();
                    }
                }
            }

            return $payment;
        });
    }
}
