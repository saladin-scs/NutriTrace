<?php

namespace Tests\Feature;

use App\Enums\ColdRoomMovementType;
use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\ColdRoom;
use App\Models\Organization;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PlatformTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ColdRoomFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformTaxonomySeeder::class);
    }

    public function test_cold_room_entry_and_exit_answer_flow_questions(): void
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::Retailer);

        $org = Organization::query()->create([
            'name' => 'Marché Test',
            'slug' => 'marche-test',
            'type' => OrganizationType::Retailer,
            'status' => OrganizationStatus::Verified,
        ]);
        $org->users()->attach($user->id, ['is_primary' => true]);

        $this->actingAs($user)->post(route('cold-rooms.store'), [
            'organization_id' => $org->id,
            'name' => 'CF Test',
            'capacity_kg' => 500,
            'target_temp_min_c' => 2,
            'target_temp_max_c' => 4,
        ])->assertRedirect();

        $room = ColdRoom::query()->firstOrFail();
        $product = Product::query()->create([
            'organization_id' => $org->id,
            'name' => 'Yaourt',
            'slug' => 'yaourt',
            'unit' => 'kg',
            'status' => 'active',
        ]);
        $batch = Batch::query()->create([
            'product_id' => $product->id,
            'organization_id' => $org->id,
            'code' => 'NT-2026-009999',
            'status' => 'active',
            'quantity' => 50,
            'unit' => 'kg',
            'produced_at' => now(),
        ]);

        $this->actingAs($user)->post(route('cold-rooms.movements.store', $room), [
            'type' => ColdRoomMovementType::Entry->value,
            'batch_id' => $batch->id,
            'quantity' => 50,
            'temperature_c' => 3,
            'humidity_pct' => 85,
            'event_label' => 'Entrée stock frais',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('cold-rooms.movements.store', $room), [
            'type' => ColdRoomMovementType::Exit->value,
            'batch_id' => $batch->id,
            'quantity' => 10,
            'temperature_c' => 3.2,
            'event_label' => 'Sortie vente',
        ])->assertRedirect();

        $this->assertDatabaseHas('cold_room_movements', [
            'cold_room_id' => $room->id,
            'batch_id' => $batch->id,
            'type' => 'entry',
        ]);
        $this->assertDatabaseHas('traceability_events', [
            'batch_id' => $batch->id,
            'type' => 'cold_storage_entry',
        ]);

        $exit = $room->movements()->where('type', 'exit')->first();
        $this->assertNotNull($exit);
        $this->assertNotNull($exit->duration_minutes);

        $this->actingAs($user)
            ->get(route('cold-rooms.show', $room))
            ->assertOk()
            ->assertSee('QUAND ?')
            ->assertSee('DANS QUELLES CONDITIONS ?')
            ->assertSee('Historique des flux');
    }
}
