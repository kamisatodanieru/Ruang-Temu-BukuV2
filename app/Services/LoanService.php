<?php

namespace App\Services;

use App\Enums\FineStatus;
use App\Enums\LoanStatus;
use App\Enums\PaymentStatus;
use App\Models\Book;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LoanService
{
    /**
     * Ajukan permintaan peminjaman 1 buku oleh Anggota.
     * Urutan row locking konsisten: User (member) -> Book.
     *
     * @throws ValidationException
     */
    public function createLoanRequest(User $member, int $bookId, ?Request $request = null): Loan
    {
        return DB::transaction(function () use ($member, $bookId, $request): Loan {
            // 1. Kunci baris user untuk mencegah race condition kuota & buku ganda
            $lockedMember = User::query()->whereKey($member->id)->lockForUpdate()->firstOrFail();

            // 2. Kunci baris buku
            $lockedBook = Book::query()->withTrashed()->whereKey($bookId)->lockForUpdate()->first();

            if (! $lockedMember->isActive() || ! $lockedMember->isAnggota()) {
                throw ValidationException::withMessages([
                    'loan' => 'Akun Anda tidak aktif atau tidak berwenang mengajukan peminjaman.',
                ]);
            }

            if ($lockedBook === null || $lockedBook->trashed()) {
                throw ValidationException::withMessages([
                    'loan' => 'Buku tidak ditemukan atau sudah tidak tersedia di katalog.',
                ]);
            }

            // 3. Periksa blokir overdue (status = borrowed, returned_at IS NULL, due_at < now())
            if ($lockedMember->overdueLoans()->exists()) {
                throw ValidationException::withMessages([
                    'loan' => 'Anda tidak dapat mengajukan peminjaman baru karena masih memiliki buku yang terlambat dikembalikan (overdue).',
                ]);
            }

            // 4. Hitung ulang kuota aktif (pending + borrowed) di dalam lock
            $maxActiveLoans = (int) config('library.max_active_loans', 3);
            $activeLoansCount = $lockedMember->activeLoans()->count();

            if ($activeLoansCount >= $maxActiveLoans) {
                throw ValidationException::withMessages([
                    'loan' => "Kuota peminjaman aktif Anda telah mencapai batas maksimal ({$maxActiveLoans} buku).",
                ]);
            }

            // 5. Pastikan tidak ada transaksi aktif (pending / borrowed) untuk buku yang sama
            $hasDuplicateActiveBook = $lockedMember->activeLoans()
                ->where('book_id', $lockedBook->id)
                ->exists();

            if ($hasDuplicateActiveBook) {
                throw ValidationException::withMessages([
                    'loan' => 'Anda sudah memiliki permintaan atau pinjaman aktif untuk buku ini.',
                ]);
            }

            // 6. Pastikan stok tersedia > 0 (Request Pending tidak mengurangi stok, tapi tidak boleh diajukan jika stok 0)
            if ($lockedBook->available_stock <= 0) {
                throw ValidationException::withMessages([
                    'loan' => 'Stok buku ini sedang habis dan tidak dapat diajukan.',
                ]);
            }

            $loan = new Loan([
                'member_id' => $lockedMember->id,
                'book_id' => $lockedBook->id,
                'requested_at' => now(),
            ]);
            $loan->status = LoanStatus::Pending;
            $loan->save();

            ActivityLogger::record(
                action: 'loan.requested',
                description: "Anggota mengajukan peminjaman buku: {$lockedBook->title}",
                subject: $loan,
                actor: $lockedMember,
                request: $request,
            );

            return $loan;
        });
    }

    /**
     * Setujui permintaan peminjaman oleh Petugas/Admin.
     * Sesuai PRD 4.4: Urutan locking konsisten (Loan -> User -> Book),
     * validasi ulang seluruh syarat bisnis, kurangi available_stock 1, ubah status menjadi borrowed.
     *
     * @throws ValidationException
     */
    public function approveLoan(Loan $loan, User $actor, ?Request $request = null): Loan
    {
        return DB::transaction(function () use ($loan, $actor, $request): Loan {
            // 1. Kunci baris Loan
            $lockedLoan = Loan::query()->whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if ($lockedLoan->status !== LoanStatus::Pending) {
                throw ValidationException::withMessages([
                    'loan' => 'Permintaan peminjaman ini sudah diproses dan tidak lagi berstatus menunggu persetujuan.',
                ]);
            }

            // 2. Kunci baris User (member) dan Book dengan urutan konsisten
            $lockedMember = User::query()->whereKey($lockedLoan->member_id)->lockForUpdate()->firstOrFail();
            $lockedBook = Book::query()->withTrashed()->whereKey($lockedLoan->book_id)->lockForUpdate()->first();

            // 3. Pastikan akun anggota masih active
            if (! $lockedMember->isActive()) {
                throw ValidationException::withMessages([
                    'loan' => 'Persetujuan ditolak karena akun anggota saat ini berstatus nonaktif.',
                ]);
            }

            // 4. Pastikan anggota tidak memiliki pinjaman overdue
            if ($lockedMember->overdueLoans()->exists()) {
                throw ValidationException::withMessages([
                    'loan' => 'Persetujuan ditolak karena anggota memiliki pinjaman buku lain yang sedang terlambat (overdue).',
                ]);
            }

            // 5. Hitung ulang kuota aktif di luar request ini dan pastikan belum mencapai batas maksimal
            $maxActiveLoans = (int) config('library.max_active_loans', 3);
            $otherActiveCount = $lockedMember->activeLoans()
                ->whereKeyNot($lockedLoan->id)
                ->count();

            if ($otherActiveCount >= $maxActiveLoans) {
                throw ValidationException::withMessages([
                    'loan' => "Persetujuan ditolak karena anggota telah mencapai batas maksimal kuota peminjaman ({$maxActiveLoans} buku).",
                ]);
            }

            // 6. Pastikan tidak ada transaksi aktif lain untuk buku yang sama
            $hasDuplicateActiveBook = $lockedMember->activeLoans()
                ->whereKeyNot($lockedLoan->id)
                ->where('book_id', $lockedLoan->book_id)
                ->exists();

            if ($hasDuplicateActiveBook) {
                throw ValidationException::withMessages([
                    'loan' => 'Persetujuan ditolak karena anggota sudah memiliki pinjaman aktif untuk judul buku yang sama.',
                ]);
            }

            // 7. Pastikan buku tidak soft-deleted dan stok tersedia > 0
            if ($lockedBook === null || $lockedBook->trashed()) {
                throw ValidationException::withMessages([
                    'loan' => 'Persetujuan ditolak karena buku telah dihapus dari katalog.',
                ]);
            }

            if ($lockedBook->available_stock <= 0) {
                throw ValidationException::withMessages([
                    'loan' => 'Persetujuan ditolak karena stok tersedia buku ini telah habis.',
                ]);
            }

            // 8. Kurangi available_stock sebesar 1
            $lockedBook->available_stock -= 1;
            $lockedBook->save();

            // 9. Isi approved_by, borrowed_at, due_at, dan ubah status menjadi borrowed
            $now = now();
            $durationDays = (int) config('library.loan_duration_days', 7);

            $lockedLoan->approved_by = $actor->id;
            $lockedLoan->borrowed_at = $now;
            $lockedLoan->due_at = $now->copy()->addDays($durationDays);
            $lockedLoan->status = LoanStatus::Borrowed;
            $lockedLoan->save();

            // 10. Catat audit log
            ActivityLogger::record(
                action: 'loan.approved',
                description: "Menyetujui peminjaman #{$lockedLoan->id} untuk buku '{$lockedBook->title}' oleh {$lockedMember->name}",
                subject: $lockedLoan,
                actor: $actor,
                request: $request,
            );

            return $lockedLoan;
        });
    }

    /**
     * Tolak permintaan peminjaman oleh Petugas/Admin.
     * Hanya untuk status Pending, wajib mencatat alasan dan pelaku.
     *
     * @throws ValidationException
     */
    public function rejectLoan(Loan $loan, User $actor, string $reason, ?Request $request = null): Loan
    {
        return DB::transaction(function () use ($loan, $actor, $reason, $request): Loan {
            $lockedLoan = Loan::query()->whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if ($lockedLoan->status !== LoanStatus::Pending) {
                throw ValidationException::withMessages([
                    'loan' => 'Hanya permintaan dengan status menunggu persetujuan yang dapat ditolak.',
                ]);
            }

            $lockedLoan->status = LoanStatus::Rejected;
            $lockedLoan->rejected_by = $actor->id;
            $lockedLoan->rejected_at = now();
            $lockedLoan->rejection_reason = trim($reason);
            $lockedLoan->save();

            ActivityLogger::record(
                action: 'loan.rejected',
                description: "Menolak permintaan peminjaman #{$lockedLoan->id}. Alasan: {$lockedLoan->rejection_reason}",
                subject: $lockedLoan,
                actor: $actor,
                request: $request,
            );

            return $lockedLoan;
        });
    }

    /**
     * Batalkan permintaan peminjaman oleh Anggota pemilik.
     * Hanya untuk status Pending milik anggota terkait.
     *
     * @throws ValidationException
     */
    public function cancelLoan(Loan $loan, User $actor, ?Request $request = null): Loan
    {
        return DB::transaction(function () use ($loan, $actor, $request): Loan {
            $lockedLoan = Loan::query()->whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if ($lockedLoan->member_id !== $actor->id) {
                abort(403, 'Anda tidak berhak membatalkan transaksi milik anggota lain.');
            }

            if ($lockedLoan->status !== LoanStatus::Pending) {
                throw ValidationException::withMessages([
                    'loan' => 'Hanya permintaan dengan status menunggu persetujuan yang dapat dibatalkan.',
                ]);
            }

            $lockedLoan->status = LoanStatus::Cancelled;
            $lockedLoan->cancelled_at = now();
            $lockedLoan->save();

            ActivityLogger::record(
                action: 'loan.cancelled',
                description: "Anggota membatalkan permintaan peminjaman #{$lockedLoan->id}.",
                subject: $lockedLoan,
                actor: $actor,
                request: $request,
            );

            return $lockedLoan;
        });
    }

    /**
     * Ajukan pengembalian buku oleh Anggota pemilik peminjaman.
     * Hanya untuk status Borrowed milik anggota terkait.
     *
     * @throws ValidationException
     */
    public function requestReturn(Loan $loan, User $actor, ?Request $request = null): Loan
    {
        return DB::transaction(function () use ($loan, $actor, $request): Loan {
            $lockedLoan = Loan::query()->whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if ($lockedLoan->member_id !== $actor->id) {
                abort(403, 'Anda tidak berhak mengajukan pengembalian untuk transaksi milik anggota lain.');
            }

            if ($lockedLoan->status !== LoanStatus::Borrowed) {
                throw ValidationException::withMessages([
                    'loan' => 'Pengajuan pengembalian hanya dapat dilakukan untuk buku yang sedang dipinjam.',
                ]);
            }

            $lockedBook = Book::query()->withTrashed()->whereKey($lockedLoan->book_id)->first();

            $lockedLoan->status = LoanStatus::ReturnPending;
            $lockedLoan->return_requested_at = now();
            $lockedLoan->save();

            ActivityLogger::record(
                action: 'loan.return_requested',
                description: "Anggota mengajukan pengembalian untuk buku '{$lockedBook?->title}' (Peminjaman #{$lockedLoan->id})",
                subject: $lockedLoan,
                actor: $actor,
                request: $request,
            );

            return $lockedLoan;
        });
    }

    /**
     * Konfirmasi pengembalian buku oleh Petugas/Admin.
     * Dapat mengonfirmasi status ReturnPending (pengajuan pengembalian dari anggota)
     * ataupun status Borrowed (pengembalian langsung saat serah fisik di loket).
     *
     * @throws ValidationException
     */
    public function returnLoan(Loan $loan, User $actor, ?Request $request = null): Loan
    {
        return DB::transaction(function () use ($loan, $actor, $request): Loan {
            // 1. Kunci baris Loan
            $lockedLoan = Loan::query()->whereKey($loan->id)->lockForUpdate()->firstOrFail();

            if (! in_array($lockedLoan->status, [LoanStatus::Borrowed, LoanStatus::ReturnPending], true) || $lockedLoan->returned_at !== null) {
                throw ValidationException::withMessages([
                    'loan' => 'Transaksi ini tidak dalam status sedang dipinjam/menunggu pengembalian atau sudah dikonfirmasi kembali sebelumnya.',
                ]);
            }

            // 2. Hitung hari keterlambatan sebelum status dan returned_at diperbarui
            $overdueDays = $lockedLoan->overdueDays();

            // 3. Kunci baris Book (termasuk jika buku sudah di-soft-delete setelah dipinjam)
            $lockedBook = Book::query()->withTrashed()->whereKey($lockedLoan->book_id)->lockForUpdate()->firstOrFail();

            // 4. Tambah available_stock tepat 1 kali (dijaga tidak melebihi total_stock)
            $lockedBook->available_stock = min($lockedBook->total_stock, $lockedBook->available_stock + 1);
            $lockedBook->save();

            // 5. Catat waktu pengembalian, petugas yang mengonfirmasi, dan ubah status menjadi Returned
            $lockedLoan->returned_at = now();
            $lockedLoan->returned_confirmed_by = $actor->id;
            $lockedLoan->status = LoanStatus::Returned;
            $lockedLoan->save();

            // 6. Jika buku dikembalikan terlambat (overdue), buat atau perbarui tagihan denda secara otoritatif
            if ($overdueDays > 0) {
                $finePerDay = (int) config('library.fine_per_day', 1000);
                $totalAmount = $overdueDays * $finePerDay;

                if ($totalAmount > 0) {
                    $existingFine = Fine::query()
                        ->where('loan_id', $lockedLoan->id)
                        ->lockForUpdate()
                        ->first();

                    if ($existingFine === null) {
                        Fine::query()->create([
                            'loan_id' => $lockedLoan->id,
                            'member_id' => $lockedLoan->member_id,
                            'overdue_days' => $overdueDays,
                            'fine_per_day' => $finePerDay,
                            'total_amount' => $totalAmount,
                            'status' => FineStatus::Unpaid,
                        ]);
                    } elseif (in_array($existingFine->status, [FineStatus::Unpaid, FineStatus::PendingPayment], true)) {
                        $amountChanged = $existingFine->total_amount !== $totalAmount
                            || $existingFine->overdue_days !== $overdueDays;

                        $existingFine->overdue_days = $overdueDays;
                        $existingFine->fine_per_day = $finePerDay;
                        $existingFine->total_amount = $totalAmount;

                        if ($amountChanged && $existingFine->status === FineStatus::PendingPayment) {
                            $existingFine->status = FineStatus::Unpaid;
                            $existingFine->payments()
                                ->where('status', PaymentStatus::Pending->value)
                                ->update(['status' => PaymentStatus::Cancelled->value]);
                        }

                        $existingFine->save();
                    } elseif ($existingFine->status === FineStatus::Paid && $overdueDays > $existingFine->overdue_days) {
                        // Jika anggota sempat membayar lebih awal namun masih menahan buku hingga hari berikutnya,
                        // tagihkan selisih hari keterlambatan yang belum dibayar.
                        $additionalDays = $overdueDays - $existingFine->overdue_days;
                        $existingFine->overdue_days = $additionalDays;
                        $existingFine->fine_per_day = $finePerDay;
                        $existingFine->total_amount = $additionalDays * $finePerDay;
                        $existingFine->status = FineStatus::Unpaid;
                        $existingFine->paid_at = null;
                        $existingFine->save();
                    }
                }
            }

            ActivityLogger::record(
                action: 'loan.returned',
                description: "Mengonfirmasi pengembalian peminjaman #{$lockedLoan->id} untuk buku '{$lockedBook->title}'",
                subject: $lockedLoan,
                actor: $actor,
                request: $request,
            );

            return $lockedLoan;
        });
    }
}
