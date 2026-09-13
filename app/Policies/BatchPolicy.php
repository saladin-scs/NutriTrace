<?php

namespace App\Policies;

use App\Models\Batch;
use App\Models\User;

class BatchPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Batch $batch): bool
    {
        return $user->isAdmin() || $user->belongsToOrganization($batch->organization);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->organizations()->exists();
    }

    public function update(User $user, Batch $batch): bool
    {
        return $user->isAdmin() || $user->belongsToOrganization($batch->organization);
    }

    public function transform(User $user, Batch $batch): bool
    {
        return $user->isAdmin() || $user->organizations()->exists();
    }

    public function distribute(User $user, Batch $batch): bool
    {
        return $user->isAdmin() || $user->belongsToOrganization($batch->organization);
    }
}
