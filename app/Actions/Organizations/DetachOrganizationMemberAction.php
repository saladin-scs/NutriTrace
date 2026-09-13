<?php

namespace App\Actions\Organizations;

use App\Domain\Identity\AuditLogger;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class DetachOrganizationMemberAction
{
    public function __construct(private AuditLogger $audit) {}

    public function execute(User $actor, Organization $organization, User $member): Organization
    {
        if (! $organization->users()->where('user_id', $member->id)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => 'Cet utilisateur n’est pas membre de l’organisation.',
            ]);
        }

        $primaries = $organization->users()->wherePivot('is_primary', true)->count();
        $isPrimary = (bool) $organization->users()
            ->where('user_id', $member->id)
            ->first()
            ?->pivot
            ?->is_primary;

        if ($isPrimary && $primaries <= 1 && $organization->users()->count() > 1) {
            throw ValidationException::withMessages([
                'user_id' => 'Désignez un autre responsable avant de retirer le membre principal.',
            ]);
        }

        $organization->users()->detach($member->id);

        $this->audit->log($actor, 'organization.member_detached', $organization, [
            'member_id' => $member->id,
        ], null);

        return $organization->fresh(['users']);
    }
}
