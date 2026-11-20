<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    /**
     * Admin dan Petugas dapat melihat daftar seluruh transaksi;
     * Anggota hanya dapat melihat daftar miliknya sendiri melalui endpoint khusus anggota.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin() || $actor->isPetugas();
    }

    /**
     * Pemeriksaan kepemilikan (ownership check) untuk mencegah IDOR:
     * Anggota hanya boleh melihat transaksi miliknya sendiri.
     */
    public function view(User $actor, Loan $loan): bool
    {
        if ($actor->isAdmin() || $actor->isPetugas()) {
            return true;
        }

        return $actor->isAnggota() && $loan->member_id === $actor->id;
    }

    /**
     * Hanya Anggota aktif yang dapat mengajukan permintaan peminjaman.
     */
    public function create(User $actor): bool
    {
        return $actor->isAnggota() && $actor->isActive();
    }

    /**
     * Hanya Anggota pemilik transaksi yang dapat membatalkan request miliknya.
     * Pemeriksaan status Pending dilakukan secara atomik di dalam lock pada LoanService.
     */
    public function cancel(User $actor, Loan $loan): bool
    {
        return $actor->isAnggota() && $loan->member_id === $actor->id;
    }

    /**
     * Hanya Anggota pemilik transaksi yang dapat mengajukan pengembalian miliknya.
     */
    public function requestReturn(User $actor, Loan $loan): bool
    {
        return $actor->isAnggota() && $loan->member_id === $actor->id;
    }

    /**
     * Hanya Petugas atau Admin yang dapat menyetujui request peminjaman.
     */
    public function approve(User $actor, Loan $loan): bool
    {
        return $actor->isAdmin() || $actor->isPetugas();
    }

    /**
     * Hanya Petugas atau Admin yang dapat menolak request peminjaman.
     */
    public function reject(User $actor, Loan $loan): bool
    {
        return $actor->isAdmin() || $actor->isPetugas();
    }

    /**
     * Hanya Petugas atau Admin yang dapat mengonfirmasi pengembalian buku.
     */
    public function returnBook(User $actor, Loan $loan): bool
    {
        return $actor->isAdmin() || $actor->isPetugas();
    }
}
