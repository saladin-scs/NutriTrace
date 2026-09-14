<?php

namespace Tests\Feature;

use App\Actions\Shipments\CreateShipmentAction;
use App\Actions\Shipments\DispatchShipmentAction;
use App\Actions\Shipments\ReceiveShipmentAction;
use App\Enums\BatchStatus;
use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Enums\ShipmentStatus;
use App\Enums\TraceabilityEventType;
use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Models\Batch;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\PlatformTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ControlTowerShipmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformTaxonomySeeder::class);
    }

    public function test_create_dispatch_and_receive_shipment_writes_traceability_events(): void
    {
        [$user, $from, $to, $batch, $vehicle] = $this->fixture();

        $shipment = app(CreateShipmentAction::class)->execute($user, [
            'vehicle_id' => $vehicle->id,
            'from_organization_id' => $from->id,
            'to_organization_id' => $to->id,
            'from_location_id' => $from->primary_location_id,
            'to_location_id' => $to->primary_location_id,
            'distance_km' => 40,
            'load_kg' => 100,
        ], [
            ['batch_id' => $batch->id, 'quantity' => 100],
        ]);

        $this->assertMatchesRegularExpression('/^SH-\d{4}-\d{6}$/', $shipment->code);
        $this->assertSame(ShipmentStatus::Draft, $shipment->status);
        $this->assertNotNull($shipment->estimated_co2e_kg);

        $shipment = app(DispatchShipmentAction::class)->execute($user, $shipment);
        $this->assertSame(ShipmentStatus::InTransit, $shipment->status);
        $this->assertSame(VehicleStatus::InTransit, $vehicle->fresh()->status);
        $this->assertSame(BatchStatus::Distributed, $batch->fresh()->status);

        $this->assertDatabaseHas('traceability_events', [
            'batch_id' => $batch->id,
            'type' => TraceabilityEventType::Dispatched->value,
        ]);
        $this->assertDatabaseHas('traceability_events', [
            'batch_id' => $batch->id,
            'type' => TraceabilityEventType::InTransit->value,
        ]);

        $shipment = app(ReceiveShipmentAction::class)->execute($user, $shipment->fresh());
        $this->assertSame(ShipmentStatus::Delivered, $shipment->status);
        $this->assertSame(VehicleStatus::Available, $vehicle->fresh()->status);
        $this->assertDatabaseHas('traceability_events', [
            'batch_id' => $batch->id,
            'type' => TraceabilityEventType::Delivered->value,
        ]);
    }

    public function test_control_tower_page_and_api(): void
    {
        [$user, $from, $to, $batch, $vehicle] = $this->fixture();

        $shipment = app(CreateShipmentAction::class)->execute($user, [
            'vehicle_id' => $vehicle->id,
            'from_organization_id' => $from->id,
            'to_organization_id' => $to->id,
        ], [['batch_id' => $batch->id]]);
        app(DispatchShipmentAction::class)->execute($user, $shipment);

        $this->actingAs($user)
            ->get(route('control-tower.index'))
            ->assertOk()
            ->assertSee('Control Tower');

        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/dashboard/control-tower')
            ->assertOk()
            ->assertJsonPath('data.kpis.active_shipments', 1);

        $this->withToken($token)
            ->getJson('/api/v1/shipments')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->withToken($token)
            ->getJson('/api/v1/batches/'.$batch->id.'/timeline')
            ->assertOk()
            ->assertJsonPath('data.batch_code', $batch->code);

        $this->withToken($token)
            ->getJson('/api/v1/vehicles')
            ->assertOk();
    }

    public function test_api_dispatch_and_receive_endpoints(): void
    {
        [$user, $from, $to, $batch, $vehicle] = $this->fixture();
        $token = $user->createToken('test')->plainTextToken;

        $create = $this->withToken($token)->postJson('/api/v1/shipments', [
            'vehicle_id' => $vehicle->id,
            'from_organization_id' => $from->id,
            'to_organization_id' => $to->id,
            'items' => [['batch_id' => $batch->id, 'quantity' => 50]],
        ])->assertCreated();

        $id = $create->json('data.id');

        $this->withToken($token)
            ->postJson("/api/v1/shipments/{$id}/dispatch")
            ->assertOk()
            ->assertJsonPath('data.status', 'in_transit');

        $this->withToken($token)
            ->postJson("/api/v1/shipments/{$id}/receive")
            ->assertOk()
            ->assertJsonPath('data.status', 'delivered');
    }

    /**
     * @return array{0:User,1:Organization,2:Organization,3:Batch,4:Vehicle}
     */
    private function fixture(): array
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(UserRole::Distributor);

        $from = Organization::query()->create([
            'name' => 'DC Test',
            'slug' => 'dc-test-'.uniqid(),
            'type' => OrganizationType::Distributor,
            'status' => OrganizationStatus::Verified,
        ]);
        $fromLoc = $from->locations()->create([
            'name' => 'DC',
            'type' => 'facility',
            'city' => 'Tunis',
            'governorate' => 'Tunis',
            'country' => 'TN',
            'latitude' => 36.8,
            'longitude' => 10.2,
            'is_primary' => true,
        ]);
        $from->update(['primary_location_id' => $fromLoc->id]);
        $from->users()->attach($user->id, ['is_primary' => true]);

        $to = Organization::query()->create([
            'name' => 'Retail Test',
            'slug' => 'retail-test-'.uniqid(),
            'type' => OrganizationType::Retailer,
            'status' => OrganizationStatus::Verified,
        ]);
        $toLoc = $to->locations()->create([
            'name' => 'Shop',
            'type' => 'facility',
            'city' => 'Sousse',
            'governorate' => 'Sousse',
            'country' => 'TN',
            'latitude' => 35.8,
            'longitude' => 10.6,
            'is_primary' => true,
        ]);
        $to->update(['primary_location_id' => $toLoc->id]);

        $product = Product::query()->create([
            'organization_id' => $from->id,
            'name' => 'Produit Test',
            'slug' => 'produit-test-'.uniqid(),
            'unit' => 'kg',
        ]);

        $batch = Batch::query()->create([
            'product_id' => $product->id,
            'organization_id' => $from->id,
            'code' => 'NT-2026-'.random_int(100000, 999999),
            'status' => BatchStatus::Active,
            'quantity' => 100,
            'unit' => 'kg',
            'produced_at' => now(),
        ]);

        $vehicle = Vehicle::query()->create([
            'organization_id' => $from->id,
            'registration' => 'TN-TEST-'.random_int(100, 999),
            'type' => VehicleType::Truck,
            'status' => VehicleStatus::Available,
            'capacity_kg' => 5000,
            'emission_factor' => 0.8,
            'driver_name' => 'Driver Test',
        ]);

        return [$user, $from->fresh(), $to->fresh(), $batch, $vehicle];
    }
}
