<?php

namespace App\Policies;

use App\Models\Fine;
use App\Models\User;

class FinePolicy
{
    /**
     * Admin dan Petugas dapat melihat detail denda seluruh anggota;
     * Anggota hanya dapat melihat tagihan denda miliknya sendiri.
     */
    public function view(User $actor, Fine $fine): bool
    {
        if ($actor->isAdmin() || $actor->isPetugas()) {
            return true;
        }

        return $actor->isAnggota() && $fine->member_id === $actor->id;
    }

    /**
     * Hanya Anggota aktif pemilik tagihan yang dapat menginisiasi pembayaran online.
     */
    public function pay(User $actor, Fine $fine): bool
    {
        return $actor->isAnggota()
            && $actor->isActive()
            && $fine->member_id === $actor->id;
    }
}
