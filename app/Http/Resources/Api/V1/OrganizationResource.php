<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Organization */
class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'email' => $this->email,
            'phone' => $this->phone,
            'description' => $this->description,
            'registration_number' => $this->registration_number,
            'tax_id' => $this->tax_id,
            'verified_at' => $this->verified_at,
            'primary_location' => $this->whenLoaded('primaryLocation', fn () => [
                'id' => $this->primaryLocation?->id,
                'city' => $this->primaryLocation?->city,
                'governorate' => $this->primaryLocation?->governorate,
                'address_line' => $this->primaryLocation?->address_line,
            ]),
            'members_count' => $this->whenCounted('users'),
            'members' => $this->whenLoaded('users', fn () => $this->users->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_primary' => (bool) $user->pivot->is_primary,
                'job_title' => $user->pivot->job_title,
                'role_id' => $user->pivot->role_id,
            ])),
            'created_at' => $this->created_at,
        ];
    }
}
