<?php

namespace App\Actions\Organizations;

use App\Domain\Identity\AuditLogger;
use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateOrganizationAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $actor, Organization $organization, array $data): Organization
    {
        return DB::transaction(function () use ($actor, $organization, $data) {
            $old = $organization->only([
                'name', 'type', 'email', 'phone', 'description',
                'registration_number', 'tax_id',
            ]);

            if (isset($data['type']) && ! $data['type'] instanceof OrganizationType) {
                $data['type'] = OrganizationType::from($data['type']);
            }

            $organization->update(collect($data)->only([
                'name', 'type', 'email', 'phone', 'description',
                'registration_number', 'tax_id',
            ])->all());

            if (array_key_exists('city', $data) || array_key_exists('address_line', $data) || array_key_exists('governorate', $data)) {
                $location = $organization->primaryLocation
                    ?? $organization->locations()->where('is_primary', true)->first();

                $payload = [
                    'address_line' => $data['address_line'] ?? $location?->address_line,
                    'city' => $data['city'] ?? $location?->city,
                    'governorate' => $data['governorate'] ?? $location?->governorate ?? 'Tunis',
                ];

                if ($location) {
                    $location->update($payload);
                } else {
                    $created = $organization->locations()->create([
                        ...$payload,
                        'name' => $organization->name.' — siège',
                        'type' => 'facility',
                        'country' => 'TN',
                        'is_primary' => true,
                    ]);
                    $organization->update(['primary_location_id' => $created->id]);
                }
            }

            $this->audit->log($actor, 'organization.updated', $organization, $old, $organization->fresh()->only([
                'name', 'type', 'email', 'phone', 'description',
                'registration_number', 'tax_id',
            ]));

            return $organization->fresh(['primaryLocation', 'users']);
        });
    }
}
