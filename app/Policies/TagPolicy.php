<?php

namespace App\Policies;

use App\Models\Tag;
use App\Models\User;

class TagPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, Tag $tag): bool
    {
        return $actor->isAdmin();
    }

    public function delete(User $actor, Tag $tag): bool
    {
        return $actor->isAdmin();
    }
}
