<?php

namespace App\Policies;

use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Shipment $shipment): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $orgIds = $user->organizations()->pluck('organizations.id');

        return $orgIds->contains($shipment->from_organization_id)
            || $orgIds->contains($shipment->to_organization_id)
            || $shipment->created_by === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function dispatch(User $user, Shipment $shipment): bool
    {
        return $this->view($user, $shipment);
    }

    public function receive(User $user, Shipment $shipment): bool
    {
        return $this->view($user, $shipment);
    }
}
