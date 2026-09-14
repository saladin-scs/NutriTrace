<?php

namespace App\Policies;

use App\Models\Anomaly;
use App\Models\User;

class AnomalyPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Anomaly $anomaly): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (! $anomaly->organization_id) {
            return true;
        }

        return $user->organizations()->where('organizations.id', $anomaly->organization_id)->exists();
    }

    public function update(User $user, Anomaly $anomaly): bool
    {
        return $this->view($user, $anomaly);
    }

    public function scan(User $user): bool
    {
        return $user->isAdmin() || $user->organizations()->exists();
    }
}
