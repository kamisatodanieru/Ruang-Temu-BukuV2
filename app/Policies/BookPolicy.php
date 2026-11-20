<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    public function viewAny(?User $actor): bool
    {
        return true;
    }

    public function view(?User $actor, Book $book): bool
    {
        return ! $book->trashed();
    }

    public function manage(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, Book $book): bool
    {
        return $actor->isAdmin();
    }

    public function delete(User $actor, Book $book): bool
    {
        return $actor->isAdmin();
    }
}
