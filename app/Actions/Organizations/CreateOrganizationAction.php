<?php

namespace App\Actions\Organizations;

use App\Domain\Identity\AuditLogger;
use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateOrganizationAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array{
     *     name: string,
     *     type: OrganizationType|string,
     *     email?: ?string,
     *     phone?: ?string,
     *     description?: ?string,
     *     registration_number?: ?string,
     *     tax_id?: ?string,
     *     city?: ?string,
     *     governorate?: ?string,
     *     address_line?: ?string,
     * }  $data
     */
    public function execute(User $actor, array $data): Organization
    {
        return DB::transaction(function () use ($actor, $data) {
            $type = $data['type'] instanceof OrganizationType
                ? $data['type']
                : OrganizationType::from($data['type']);

            $organization = Organization::query()->create([
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'type' => $type,
                'status' => OrganizationStatus::Pending,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'description' => $data['description'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
                'tax_id' => $data['tax_id'] ?? null,
            ]);

            if (! empty($data['city']) || ! empty($data['address_line']) || ! empty($data['governorate'])) {
                $location = $organization->locations()->create([
                    'name' => $data['name'].' — siège',
                    'type' => 'facility',
                    'address_line' => $data['address_line'] ?? null,
                    'city' => $data['city'] ?? null,
                    'governorate' => $data['governorate'] ?? 'Tunis',
                    'country' => 'TN',
                    'is_primary' => true,
                ]);
                $organization->update(['primary_location_id' => $location->id]);
            }

            $role = Role::query()->where('slug', $type->value)->first()
                ?? Role::query()->where('slug', 'producer')->first();

            $organization->users()->attach($actor->id, [
                'role_id' => $role?->id,
                'is_primary' => true,
                'job_title' => 'Responsable',
            ]);

            $this->audit->log($actor, 'organization.created', $organization, null, [
                'name' => $organization->name,
                'type' => $organization->type->value,
                'status' => $organization->status->value,
            ]);

            return $organization->fresh(['primaryLocation', 'users']);
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'organisation';
        $slug = $base;
        $i = 1;

        while (Organization::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
