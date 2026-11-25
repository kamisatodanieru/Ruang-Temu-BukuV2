<?php

namespace App\Services;

use App\Enums\FineStatus;
use App\Enums\PaymentStatus;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
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
     * Pastikan tagihan denda dibuat atau diperbarui secara kumulatif untuk pinjaman yang sedang overdue.
     * Panggilan jaringan sinkronisasi maupun pembatalan Midtrans selalu dieksekusi di luar DB lock.
     */
    public function ensureFineForLoan(Loan $loan): ?Fine
    {
        $existingFine = Fine::query()->where('loan_id', $loan->id)->first();

        if ($existingFine !== null) {
            $this->syncPaymentStatusFromMidtrans($existingFine);
        }

        if (! $loan->isOverdue()) {
            return $existingFine?->fresh() ?? ($loan->relationLoaded('fine') ? $loan->fine : null);
        }

        $overdueDays = $loan->overdueDays();
        $finePerDay = (int) config('library.fine_per_day', 1000);
        $totalAmount = $overdueDays * $finePerDay;

        if ($totalAmount <= 0) {
            return $existingFine;
        }

        $cancelledOrderIds = [];

        $fine = DB::transaction(function () use ($loan, $overdueDays, $finePerDay, $totalAmount, &$cancelledOrderIds): Fine {
            $lockedFine = Fine::query()
                ->where('loan_id', $loan->id)
                ->lockForUpdate()
                ->first();

            if ($lockedFine === null) {
                try {
                    $newFine = new Fine([
                        'loan_id' => $loan->id,
                        'member_id' => $loan->member_id,
                        'overdue_days' => $overdueDays,
                        'fine_per_day' => $finePerDay,
                        'total_amount' => $totalAmount,
                    ]);
                    $newFine->paid_amount = 0;
                    $newFine->status = FineStatus::Unpaid;
                    $newFine->save();

                    return $newFine;
                } catch (UniqueConstraintViolationException) {
                    $lockedFine = Fine::query()
                        ->where('loan_id', $loan->id)
                        ->lockForUpdate()
                        ->firstOrFail();
                }
            }

            $cancelledOrderIds = $this->reconcileFineState($lockedFine, $overdueDays, $finePerDay);

            return $lockedFine;
        });

        $this->cancelOrdersOnMidtrans($cancelledOrderIds);

        return $fine;
    }

    /**
     * Rekonsiliasi total denda kumulatif, nominal terbayar, dan sisa tagihan di dalam transaksi terkunci.
     * Urutan penguncian selalu konsisten: Fine -> Payment.
     *
     * @return list<string> Daftar order_id pembayaran pending usang yang perlu dibatalkan ke Midtrans di luar transaksi DB.
     */
    public function reconcileFineState(Fine $lockedFine, int $overdueDays, int $finePerDay): array
    {
        $targetDays = max((int) $lockedFine->overdue_days, $overdueDays);
        $targetTotal = max((int) $lockedFine->total_amount, $targetDays * $finePerDay);

        $settledSum = (int) $lockedFine->payments()
            ->where('status', PaymentStatus::Paid->value)
            ->sum('gross_amount');

        $paidAmount = max((int) $lockedFine->paid_amount, $settledSum);
        if ($lockedFine->status === FineStatus::Paid && $paidAmount === 0) {
            $paidAmount = (int) $lockedFine->total_amount;
        }

        $lockedFine->overdue_days = $targetDays;
        $lockedFine->fine_per_day = $finePerDay;
        $lockedFine->total_amount = $targetTotal;
        $lockedFine->paid_amount = min($targetTotal, $paidAmount);

        if ($lockedFine->status === FineStatus::Waived) {
            if ($lockedFine->isDirty()) {
                $lockedFine->save();
            }

            return [];
        }

        $cancelledOrderIds = [];
        $remaining = max(0, $targetTotal - $lockedFine->paid_amount);

        if ($remaining === 0 && $targetTotal > 0) {
            $cancelledOrderIds = $this->cancelLocalPendingPayments($lockedFine);
            $lockedFine->status = FineStatus::Paid;
            $lockedFine->paid_at = $lockedFine->paid_at ?? now();
        } else {
            $mismatchedOrders = $lockedFine->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->where('gross_amount', '!=', $remaining)
                ->pluck('order_id')
                ->all();

            if ($mismatchedOrders !== []) {
                $lockedFine->payments()
                    ->whereIn('order_id', $mismatchedOrders)
                    ->where('status', PaymentStatus::Pending->value)
                    ->update(['status' => PaymentStatus::Cancelled->value]);

                $cancelledOrderIds = array_values(array_filter(array_map('strval', $mismatchedOrders)));
            }

            $hasValidPending = $lockedFine->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->where('gross_amount', $remaining)
                ->exists();

            $lockedFine->status = $hasValidPending ? FineStatus::PendingPayment : FineStatus::Unpaid;
            $lockedFine->paid_at = null;
        }

        if ($lockedFine->isDirty()) {
            $lockedFine->save();
        }

        return $cancelledOrderIds;
    }

    /**
     * Sinkronisasi status seluruh pembayaran Pending langsung ke Midtrans API
     * (berguna pada localhost tanpa webhook publik atau sebelum pergantian hari/pengembalian buku).
     */
    public function syncPaymentStatusFromMidtrans(Fine $fine, bool $bypassCooldown = false): void
    {
        if (trim((string) config('midtrans.server_key')) === '') {
            return;
        }

        $pendingPayments = $fine->payments()
            ->select(['id', 'order_id', 'status'])
            ->where('status', PaymentStatus::Pending->value)
            ->orderBy('id')
            ->get();

        if ($pendingPayments->isEmpty()) {
            return;
        }

        foreach ($pendingPayments as $pendingPayment) {
            if (! $pendingPayment->order_id) {
                continue;
            }

            if (! $bypassCooldown) {
                $cooldownKey = "fine_midtrans_sync:{$fine->id}:{$pendingPayment->order_id}";
                if (! Cache::add($cooldownKey, true, 15)) {
                    continue;
                }
            }

            try {
                $statusResponse = (array) $this->midtransService->getTransactionStatus($pendingPayment->order_id);
                if (! empty($statusResponse['transaction_status'])) {
                    $statusResponse['order_id'] = $pendingPayment->order_id;
                    $this->handleWebhookNotification($statusResponse);
                }
            } catch (Throwable) {
                // Abaikan jika transaksi belum tercatat di Midtrans (misal user baru membuka popup tapi belum memilih metode bayar)
            }
        }
    }

    /**
     * Batalkan order lama pada sisi Midtrans di luar transaksi database agar VA/QRIS usang tidak aktif ganda.
     *
     * @param  list<string>  $orderIds
     */
    public function cancelOrdersOnMidtrans(array $orderIds): void
    {
        if ($orderIds === [] || trim((string) config('midtrans.server_key')) === '') {
            return;
        }

        foreach (array_unique($orderIds) as $orderId) {
            if ($orderId === '') {
                continue;
            }

            try {
                $this->midtransService->cancelTransaction($orderId);
            } catch (Throwable) {
                // Abaikan jika transaksi belum dipilih kanal pembayarannya di Midtrans (404) atau sudah kedaluwarsa
            }
        }
    }

    /**
     * Buat transaksi pembayaran Midtrans Snap untuk sisa tagihan denda.
     * Arsitektur 2-fase mencegah penahanan row lock DB selama pemanggilan jaringan HTTP ke Midtrans.
     *
     * @throws ValidationException
     */
    public function initiatePayment(Fine $fine, User $actor, ?Request $request = null): Payment
    {
        // Sinkronisasi awal di luar lock agar pembayaran yang baru saja selesai tidak ditagih ulang
        $this->syncPaymentStatusFromMidtrans($fine, bypassCooldown: true);

        $reusablePayment = null;
        $staleOrderIds = [];
        $snapPayload = [];
        $orderId = '';
        $amountToPay = 0;

        // Fase 1: Kunci Fine singkat untuk validasi otorisasi, rekalkulasi sisa denda, dan persiapan parameter
        DB::transaction(function () use (
            $fine,
            $actor,
            &$reusablePayment,
            &$staleOrderIds,
            &$snapPayload,
            &$orderId,
            &$amountToPay
        ): void {
            $lockedFine = Fine::query()
                ->with(['loan.book', 'member', 'latestPayment'])
                ->whereKey($fine->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedFine->member_id !== $actor->id) {
                abort(403, 'Anda tidak berwenang membayar tagihan denda milik anggota lain.');
            }

            if ($lockedFine->loan !== null && $lockedFine->loan->isOverdue()) {
                $staleOrderIds = $this->reconcileFineState(
                    $lockedFine,
                    $lockedFine->loan->overdueDays(),
                    (int) config('library.fine_per_day', 1000)
                );
                $lockedFine->load('latestPayment');
            }

            if (! $lockedFine->isPayable()) {
                throw ValidationException::withMessages([
                    'fine' => 'Tagihan denda ini sudah lunas atau dibebaskan.',
                ]);
            }

            $amountToPay = $lockedFine->remainingAmount();
            $unpaidDays = $lockedFine->unpaidDays();
            $existingPending = $lockedFine->latestPayment;

            // Gunakan kembali pembayaran Pending yang masih aktif jika nominal sisa tagihan belum berubah
            if (
                $existingPending !== null
                && $existingPending->status === PaymentStatus::Pending
                && ! empty($existingPending->snap_token)
                && (int) $existingPending->gross_amount === $amountToPay
                && $existingPending->created_at !== null
                && $existingPending->created_at->greaterThan(now()->subHours(23))
            ) {
                if ($lockedFine->status !== FineStatus::PendingPayment) {
                    $lockedFine->status = FineStatus::PendingPayment;
                    $lockedFine->save();
                }

                $reusablePayment = $existingPending;

                return;
            }

            $moreStale = $this->cancelLocalPendingPayments($lockedFine);
            $staleOrderIds = array_values(array_unique(array_merge($staleOrderIds, $moreStale)));

            $orderId = 'FINE-'.$lockedFine->id.'-'.time().'-'.Str::upper(Str::random(4));
            $bookTitle = $lockedFine->loan?->book?->title ?? 'Buku #'.$lockedFine->loan_id;
            $unitPrice = $unpaidDays > 0 && ($amountToPay % $unpaidDays === 0)
                ? intdiv($amountToPay, $unpaidDays)
                : $amountToPay;
            $itemQty = $unitPrice === $amountToPay ? 1 : $unpaidDays;

            $snapPayload = [
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $amountToPay,
                ],
                'item_details' => [
                    [
                        'id' => 'FINE-'.$lockedFine->id,
                        'price' => $unitPrice,
                        'quantity' => $itemQty,
                        'name' => Str::limit("Denda Terlambat ({$unpaidDays} hr): {$bookTitle}", 50, ''),
                    ],
                ],
                'customer_details' => [
                    'first_name' => $lockedFine->member->name,
                    'email' => $lockedFine->member->email,
                ],
            ];
        });

        $this->cancelOrdersOnMidtrans($staleOrderIds);

        if ($reusablePayment !== null) {
            return $reusablePayment;
        }

        // Fase 2: Panggilan HTTP ke Midtrans dilakukan sepenuhnya di luar transaksi & row lock database
        $snapToken = $this->midtransService->createSnapToken($snapPayload);

        // Fase 3: Kunci Fine kembali secara singkat untuk menyimpan token baru secara atomik
        try {
            return DB::transaction(function () use ($fine, $actor, $orderId, $amountToPay, $snapToken, $request): Payment {
                $lockedFine = Fine::query()
                    ->whereKey($fine->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $lockedFine->isPayable()) {
                    throw ValidationException::withMessages([
                        'fine' => 'Tagihan denda ini sudah lunas atau dibebaskan.',
                    ]);
                }

                $payment = new Payment([
                    'order_id' => $orderId,
                    'fine_id' => $lockedFine->id,
                    'user_id' => $actor->id,
                    'gross_amount' => $amountToPay,
                    'snap_token' => $snapToken,
                ]);
                $payment->status = PaymentStatus::Pending;
                $payment->save();

                $lockedFine->status = FineStatus::PendingPayment;
                $lockedFine->save();

                $formattedAmount = 'Rp '.number_format($amountToPay, 0, ',', '.');

                ActivityLogger::record(
                    action: 'fine.payment_initiated',
                    description: "Memulai pembayaran denda #{$lockedFine->id} dengan Order ID {$orderId} sebesar {$formattedAmount}",
                    subject: $lockedFine,
                    actor: $actor,
                    request: $request,
                );

                return $payment;
            });
        } catch (ValidationException $e) {
            $this->cancelOrdersOnMidtrans([$orderId]);

            throw $e;
        }
    }

    /**
     * Proses payload webhook notifikasi dari Midtrans dan perbarui status Payment serta Fine.
     * Urutan penguncian konsisten dengan alur lainnya: Fine -> Payment (mencegah deadlock AB-BA).
     *
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhookNotification(array $payload): ?Payment
    {
        $orderId = (string) ($payload['order_id'] ?? '');
        if ($orderId === '') {
            return null;
        }

        $fineId = Payment::query()->where('order_id', $orderId)->value('fine_id');
        if ($fineId === null) {
            return null;
        }

        $cancelledOrderIds = [];

        $payment = DB::transaction(function () use ($fineId, $orderId, $payload, &$cancelledOrderIds): ?Payment {
            // 1. Kunci Fine terlebih dahulu (urutan kanonik: Fine -> Payment)
            $fine = Fine::query()
                ->whereKey($fineId)
                ->lockForUpdate()
                ->first();

            // 2. Kunci Payment
            $payment = Payment::query()
                ->with('user:id,name,email')
                ->where('order_id', $orderId)
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                return null;
            }

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

                if ($wasAlreadyPaid) {
                    return $payment;
                }

                if ($fine !== null && $fine->status !== FineStatus::Waived) {
                    $settledSum = (int) $fine->payments()
                        ->where('status', PaymentStatus::Paid->value)
                        ->sum('gross_amount');

                    $newPaidAmount = min((int) $fine->total_amount, max((int) $fine->paid_amount, $settledSum));
                    $fine->paid_amount = $newPaidAmount;
                    $remaining = max(0, (int) $fine->total_amount - $newPaidAmount);

                    if ($remaining === 0) {
                        $cancelledOrderIds = $this->cancelLocalPendingPayments($fine);
                        $fine->status = FineStatus::Paid;
                        $fine->paid_at = $fine->paid_at ?? now();
                        $fine->save();

                        ActivityLogger::record(
                            action: 'fine.paid',
                            description: "Pembayaran denda #{$fine->id} lunas via Midtrans ({$paymentType}) dengan Order ID {$orderId}",
                            subject: $fine,
                            actor: $payment->user,
                        );
                    } elseif ((int) $payment->gross_amount > 0) {
                        $mismatchedOrders = $fine->payments()
                            ->where('status', PaymentStatus::Pending->value)
                            ->where('gross_amount', '!=', $remaining)
                            ->pluck('order_id')
                            ->all();

                        if ($mismatchedOrders !== []) {
                            $fine->payments()
                                ->whereIn('order_id', $mismatchedOrders)
                                ->where('status', PaymentStatus::Pending->value)
                                ->update(['status' => PaymentStatus::Cancelled->value]);
                            $cancelledOrderIds = array_values(array_filter(array_map('strval', $mismatchedOrders)));
                        }

                        $hasValidPending = $fine->payments()
                            ->where('status', PaymentStatus::Pending->value)
                            ->where('gross_amount', $remaining)
                            ->exists();

                        $fine->status = $hasValidPending ? FineStatus::PendingPayment : FineStatus::Unpaid;
                        $fine->paid_at = null;
                        $fine->save();

                        ActivityLogger::record(
                            action: 'fine.paid',
                            description: "Pembayaran denda sebagian #{$fine->id} diterima via Midtrans ({$paymentType}) dengan Order ID {$orderId}",
                            subject: $fine,
                            actor: $payment->user,
                        );
                    }
                }
            } elseif ($transactionStatus === 'pending') {
                if (! in_array($payment->status, [PaymentStatus::Paid, PaymentStatus::Cancelled], true)) {
                    $payment->status = PaymentStatus::Pending;
                }
                $payment->save();
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

        $this->cancelOrdersOnMidtrans($cancelledOrderIds);

        return $payment;
    }

    /**
     * Batalkan seluruh baris Payment berstatus Pending milik sebuah Fine di dalam DB lock.
     *
     * @return list<string>
     */
    private function cancelLocalPendingPayments(Fine $lockedFine): array
    {
        $pendingOrders = $lockedFine->payments()
            ->where('status', PaymentStatus::Pending->value)
            ->pluck('order_id')
            ->all();

        if ($pendingOrders !== []) {
            $lockedFine->payments()
                ->whereIn('order_id', $pendingOrders)
                ->where('status', PaymentStatus::Pending->value)
                ->update(['status' => PaymentStatus::Cancelled->value]);
        }

        return array_values(array_filter(array_map('strval', $pendingOrders)));
    }
}
