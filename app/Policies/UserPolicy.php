<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Hanya Admin yang berhak mengelola daftar akun pengguna.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    /**
     * Pengguna hanya dapat melihat profilnya sendiri, kecuali Admin dan Petugas pelayanan.
     */
    public function view(User $actor, User $target): bool
    {
        return $actor->isAdmin() || $actor->isPetugas() || $actor->id === $target->id;
    }

    /**
     * Hanya Admin yang dapat membuat akun Petugas atau Admin baru.
     */
    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    /**
     * Pengguna hanya dapat mengubah profil miliknya sendiri.
     */
    public function updateProfile(User $actor, User $target): bool
    {
        return $actor->id === $target->id;
    }

    /**
     * Hanya Admin yang dapat mengaktifkan/menonaktifkan akun,
     * dan Admin dilarang menonaktifkan akunnya sendiri (mencegah self-lockout).
     */
    public function toggleStatus(User $actor, User $target): bool
    {
        return $actor->isAdmin() && $actor->id !== $target->id;
    }

    /**
     * Hanya Admin yang dapat mengubah role pengguna lain, bukan dirinya sendiri.
     */
    public function changeRole(User $actor, User $target): bool
    {
        return $actor->isAdmin() && $actor->id !== $target->id;
    }
}
