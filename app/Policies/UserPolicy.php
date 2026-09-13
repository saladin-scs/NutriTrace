<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $actor, User $model): bool
    {
        return $actor->isAdmin() || $actor->id === $model->id;
    }

    public function updateRole(User $actor, User $model): bool
    {
        return $actor->isAdmin() && $actor->id !== $model->id;
    }
}
