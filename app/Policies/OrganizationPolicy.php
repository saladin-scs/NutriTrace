<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Organization $organization): bool
    {
        return $user->isAdmin() || $user->belongsToOrganization($organization);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Organization $organization): bool
    {
        return $user->isAdmin() || $user->isPrimaryMemberOf($organization);
    }

    public function delete(User $user, Organization $organization): bool
    {
        return $user->isAdmin();
    }

    public function verify(User $user, Organization $organization): bool
    {
        return $user->isAdmin();
    }

    public function manageMembers(User $user, Organization $organization): bool
    {
        return $user->isAdmin() || $user->isPrimaryMemberOf($organization);
    }
}
