<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, Category $category): bool
    {
        return $actor->isAdmin();
    }

    public function delete(User $actor, Category $category): bool
    {
        return $actor->isAdmin();
    }
}
