<?php

namespace App\Actions\ColdRooms;

use App\Domain\Identity\AuditLogger;
use App\Enums\ColdRoomStatus;
use App\Models\ColdRoom;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateColdRoomAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $actor, Organization $organization, array $data): ColdRoom
    {
        return DB::transaction(function () use ($actor, $organization, $data) {
            $room = ColdRoom::query()->create([
                'organization_id' => $organization->id,
                'owner_organization_id' => $data['owner_organization_id'] ?? $organization->id,
                'location_id' => $data['location_id'] ?? $organization->primary_location_id,
                'responsible_user_id' => $data['responsible_user_id'] ?? $actor->id,
                'name' => $data['name'],
                'code' => $data['code'] ?? $this->uniqueCode($organization->id, $data['name']),
                'type' => $data['type'] ?? \App\Enums\ColdRoomType::Refrigerated,
                'status' => ColdRoomStatus::Active,
                'capacity_kg' => $data['capacity_kg'] ?? null,
                'occupied_capacity_kg' => 0,
                'target_temp_min_c' => $data['target_temp_min_c'] ?? null,
                'target_temp_max_c' => $data['target_temp_max_c'] ?? null,
                'current_temperature_c' => $data['current_temperature_c'] ?? $data['target_temp_min_c'] ?? null,
                'humidity_min_pct' => $data['humidity_min_pct'] ?? null,
                'humidity_max_pct' => $data['humidity_max_pct'] ?? null,
                'energy_kwh_day' => $data['energy_kwh_day'] ?? null,
                'description' => $data['description'] ?? null,
            ]);

            $this->audit->log($actor, 'cold_room.created', $room, null, [
                'code' => $room->code,
                'organization_id' => $organization->id,
            ]);

            return $room->fresh(['organization', 'location', 'responsible']);
        });
    }

    private function uniqueCode(int $organizationId, string $name): string
    {
        $base = 'CF-'.strtoupper(Str::slug(Str::limit($name, 12, ''), ''));
        $base = $base === 'CF-' ? 'CF-ROOM' : $base;
        $code = $base.'-'.$organizationId;
        $i = 1;

        while (ColdRoom::withTrashed()->where('code', $code)->exists()) {
            $code = $base.'-'.$organizationId.'-'.$i++;
        }

        return $code;
    }
}
