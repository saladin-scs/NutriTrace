<?php

namespace App\Actions\Organizations;

use App\Domain\Identity\AuditLogger;
use App\Enums\OrganizationStatus;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class VerifyOrganizationAction
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(User $admin, Organization $organization, OrganizationStatus $status): Organization
    {
        if (! in_array($status, [
            OrganizationStatus::Verified,
            OrganizationStatus::Rejected,
            OrganizationStatus::Suspended,
            OrganizationStatus::Pending,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => 'Statut organisation invalide.',
            ]);
        }

        $old = ['status' => $organization->status->value];

        $organization->update([
            'status' => $status,
            'verified_by' => $status === OrganizationStatus::Verified ? $admin->id : $organization->verified_by,
            'verified_at' => $status === OrganizationStatus::Verified ? now() : $organization->verified_at,
        ]);

        $this->audit->log($admin, 'organization.status_changed', $organization, $old, [
            'status' => $status->value,
        ]);

        return $organization->fresh();
    }
}
