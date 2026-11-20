<?php

namespace App\Policies;

use App\Models\User;

class ActivityLogPolicy
{
    /**
     * Hanya Admin yang diizinkan membaca audit log.
     * Audit log bersifat append-only dan tidak dapat diubah/dihapus melalui UI.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }
}
