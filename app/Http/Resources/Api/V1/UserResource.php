<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role?->value,
            'role_label' => $this->role?->label(),
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at,
            'organizations' => $this->whenLoaded('organizations', fn () => $this->organizations->map(fn ($org) => [
                'id' => $org->id,
                'name' => $org->name,
                'type' => $org->type?->value,
                'status' => $org->status?->value,
                'is_primary' => (bool) $org->pivot->is_primary,
            ])),
        ];
    }
}
