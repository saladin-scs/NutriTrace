<?php

namespace App\Policies;

use App\Models\ColdRoom;
use App\Models\User;

class ColdRoomPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ColdRoom $coldRoom): bool
    {
        return $user->isAdmin() || $user->belongsToOrganization($coldRoom->organization);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->organizations()->exists();
    }

    public function update(User $user, ColdRoom $coldRoom): bool
    {
        return $user->isAdmin() || $user->belongsToOrganization($coldRoom->organization);
    }

    public function recordMovement(User $user, ColdRoom $coldRoom): bool
    {
        return $this->update($user, $coldRoom);
    }
}
