<?php

namespace App\Actions\Users;

use App\Domain\Identity\AuditLogger;
use App\Enums\UserRole;
use App\Models\User;

class AssignPlatformRoleAction
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(User $admin, User $user, UserRole $role): User
    {
        $old = ['role' => $user->role?->value];

        $user->assignRole($role);

        $this->audit->log($admin, 'user.role_assigned', $user, $old, [
            'role' => $role->value,
        ]);

        return $user->fresh();
    }
}
