<?php

namespace App\Actions\Organizations;

use App\Domain\Identity\AuditLogger;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AttachOrganizationMemberAction
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(
        User $actor,
        Organization $organization,
        User $member,
        ?Role $role = null,
        bool $isPrimary = false,
        ?string $jobTitle = null,
    ): Organization {
        if ($organization->users()->where('user_id', $member->id)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => 'Cet utilisateur est déjà membre de l’organisation.',
            ]);
        }

        $organization->users()->attach($member->id, [
            'role_id' => $role?->id,
            'is_primary' => $isPrimary,
            'job_title' => $jobTitle,
        ]);

        $this->audit->log($actor, 'organization.member_attached', $organization, null, [
            'member_id' => $member->id,
            'role_id' => $role?->id,
        ]);

        return $organization->fresh(['users']);
    }
}
