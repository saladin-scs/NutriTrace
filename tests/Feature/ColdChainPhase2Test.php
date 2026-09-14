<?php

namespace Tests\Feature;

use App\Enums\ColdRoomMovementType;
use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Enums\StorageRecordStatus;
use App\Enums\UserRole;
use App\Facades\ColdChain;
use App\Models\Batch;
use App\Models\ColdRoom;
use App\Models\Organization;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StorageRecord;
use App\Models\User;
use Database\Seeders\PlatformTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColdChainPhase2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformTaxonomySeeder::class);
    }

    public function test_entry_updates_storage_stock_and_occupancy_with_fefo_exit(): void
    {
        [$user, $room, $batchEarly, $batchLate] = $this->fixture();

        $this->actingAs($user)->post(route('cold-rooms.movements.store', $room), [
            'type' => ColdRoomMovementType::Entry->value,
            'batch_id' => $batchLate->id,
            'quantity' => 100,
            'temperature_c' => 3.5,
            'event_label' => 'Entrée lot tardif',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('cold-rooms.movements.store', $room), [
            'type' => ColdRoomMovementType::Entry->value,
            'batch_id' => $batchEarly->id,
            'quantity' => 80,
            'temperature_c' => 3.2,
            'event_label' => 'Entrée lot prioritaire FEFO',
        ])->assertRedirect();

        $room->refresh();
        $this->assertEquals(180.0, (float) $room->occupied_capacity_kg);
        $this->assertDatabaseCount('storage_records', 2);
        $this->assertDatabaseCount('stock_movements', 2);

        $this->actingAs($user)->post(route('cold-rooms.movements.store', $room), [
            'type' => ColdRoomMovementType::Exit->value,
            'batch_id' => $batchEarly->id,
            'quantity' => 80,
            'temperature_c' => 3.4,
            'event_label' => 'Sortie FEFO',
        ])->assertRedirect();

        $this->assertDatabaseHas('storage_records', [
            'batch_id' => $batchEarly->id,
            'status' => StorageRecordStatus::Released->value,
            'remaining_quantity' => 0,
        ]);

        $room->refresh();
        $this->assertEquals(100.0, (float) $room->occupied_capacity_kg);

        $twin = ColdChain::twin($room);
        $this->assertSame(100.0, $twin['kpis']['occupied_kg']);
        $this->assertNotEmpty($twin['open_stock']);

        $this->actingAs($user)
            ->get(route('cold-rooms.show', $room))
            ->assertOk()
            ->assertSee('Digital Twin')
            ->assertSee('File FEFO');
    }

    public function test_capacity_risk_blocks_overfill(): void
    {
        [$user, $room, $batchEarly] = $this->fixture(capacity: 50);

        $this->actingAs($user)->post(route('cold-rooms.movements.store', $room), [
            'type' => ColdRoomMovementType::Entry->value,
            'batch_id' => $batchEarly->id,
            'quantity' => 80,
            'temperature_c' => 3,
        ])->assertSessionHasErrors();

        $this->assertSame(0, StorageRecord::query()->count());
    }

    public function test_temperature_and_mass_balance_api(): void
    {
        [$user, $room, $batchEarly] = $this->fixture();
        $token = $user->createToken('test')->plainTextToken;

        $this->actingAs($user)->post(route('cold-rooms.movements.store', $room), [
            'type' => ColdRoomMovementType::Entry->value,
            'batch_id' => $batchEarly->id,
            'quantity' => 40,
            'temperature_c' => 3,
        ])->assertRedirect();

        $this->withToken($token)
            ->postJson('/api/v1/cold-rooms/'.$room->id.'/temperature', ['temperature_c' => 11.5])
            ->assertOk()
            ->assertJsonPath('data.status', 'critical');

        $this->withToken($token)
            ->getJson('/api/v1/cold-rooms/'.$room->id.'/twin')
            ->assertOk()
            ->assertJsonPath('data.room.code', $room->code);

        $this->withToken($token)
            ->getJson('/api/v1/batches/'.$batchEarly->id.'/mass-balance')
            ->assertOk()
            ->assertJsonStructure(['data' => ['input_kg', 'stored_kg', 'balanced']]);

        $this->assertGreaterThan(0, StockMovement::query()->count());
    }

    /**
     * @return array{0:User,1:ColdRoom,2:Batch,3?:Batch}
     */
    private function fixture(float $capacity = 2000): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(UserRole::Distributor);

        $org = Organization::query()->create([
            'name' => 'DC Cold Test',
            'slug' => 'dc-cold-'.uniqid(),
            'type' => OrganizationType::Distributor,
            'status' => OrganizationStatus::Verified,
        ]);
        $org->users()->attach($user->id, ['is_primary' => true]);

        $room = ColdRoom::query()->create([
            'organization_id' => $org->id,
            'owner_organization_id' => $org->id,
            'name' => 'CF Twin',
            'code' => 'CF-TWIN-'.random_int(100, 999),
            'type' => 'refrigerated',
            'status' => 'active',
            'capacity_kg' => $capacity,
            'occupied_capacity_kg' => 0,
            'target_temp_min_c' => 2,
            'target_temp_max_c' => 6,
            'current_temperature_c' => 4,
            'responsible_user_id' => $user->id,
        ]);

        $product = Product::query()->create([
            'organization_id' => $org->id,
            'name' => 'Pommes de terre',
            'slug' => 'pdt-'.uniqid(),
            'unit' => 'kg',
            'status' => 'active',
        ]);

        $batchEarly = Batch::query()->create([
            'product_id' => $product->id,
            'organization_id' => $org->id,
            'code' => 'NT-2026-'.random_int(100000, 199999),
            'status' => 'active',
            'quantity' => 200,
            'unit' => 'kg',
            'produced_at' => now()->subDays(5),
            'expires_at' => now()->addDays(2),
        ]);

        $batchLate = Batch::query()->create([
            'product_id' => $product->id,
            'organization_id' => $org->id,
            'code' => 'NT-2026-'.random_int(200000, 299999),
            'status' => 'active',
            'quantity' => 200,
            'unit' => 'kg',
            'produced_at' => now()->subDay(),
            'expires_at' => now()->addDays(12),
        ]);

        return [$user, $room, $batchEarly, $batchLate];
    }
}
