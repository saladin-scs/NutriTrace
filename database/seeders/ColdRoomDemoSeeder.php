<?php

namespace Database\Seeders;

use App\Actions\ColdRooms\CreateColdRoomAction;
use App\Actions\ColdRooms\RecordColdRoomMovementAction;
use App\Enums\ColdRoomMovementType;
use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\ColdRoom;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ColdRoomDemoSeeder extends Seeder
{
    public function run(): void
    {
        $createRoom = app(CreateColdRoomAction::class);
        $record = app(RecordColdRoomMovementAction::class);

        $retailer = User::query()->updateOrCreate(
            ['email' => 'retailer@nutritrace.test'],
            [
                'name' => 'Commerçant Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $retailer->assignRole(UserRole::Retailer);

        $shop = Organization::query()->where('name', 'like', 'Marché Local%')->first();
        if (! $shop) {
            return;
        }

        if (! $retailer->belongsToOrganization($shop)) {
            $shop->users()->syncWithoutDetaching([
                $retailer->id => ['is_primary' => true],
            ]);
        }

        $room = ColdRoom::query()->where('code', 'CF-LAC2-A')->first()
            ?? $createRoom->execute($retailer, $shop, [
                'name' => 'Chambre froide Lac 2 — A',
                'code' => 'CF-LAC2-A',
                'capacity_kg' => 2000,
                'target_temp_min_c' => 2,
                'target_temp_max_c' => 4,
                'humidity_min_pct' => 80,
                'humidity_max_pct' => 90,
                'description' => 'Nœud de distribution du Marché Local Lac 2 — flux lots frais.',
            ]);

        $batch = Batch::query()->where('code', 'NT-2026-000002')->first()
            ?? Batch::query()->latest('id')->first();

        if (! $batch || $room->movements()->exists()) {
            return;
        }

        $record->execute($retailer, $room, [
            'type' => ColdRoomMovementType::Entry,
            'batch_id' => $batch->id,
            'quantity' => 480,
            'unit' => 'kg',
            'temperature_c' => 3.2,
            'humidity_pct' => 86,
            'occurred_at' => now()->subDay()->addHours(3),
            'event_label' => 'Entrée après réception distribution',
            'from_organization_id' => Organization::query()->where('name', 'like', 'Atelier Vert%')->value('id'),
        ]);

        $record->execute($retailer, $room, [
            'type' => ColdRoomMovementType::Exit,
            'batch_id' => $batch->id,
            'quantity' => 120,
            'unit' => 'kg',
            'temperature_c' => 3.5,
            'humidity_pct' => 85,
            'occurred_at' => now()->subHours(6),
            'event_label' => 'Sortie rayon frais',
            'to_organization_id' => $shop->id,
        ]);
    }
}
