<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Product $product): bool
    {
        return $user->isAdmin() || $user->belongsToOrganization($product->organization);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->organizations()->exists();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin() || $user->belongsToOrganization($product->organization);
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isAdmin() || $user->isPrimaryMemberOf($product->organization);
    }
}
