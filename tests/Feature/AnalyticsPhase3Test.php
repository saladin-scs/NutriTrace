<?php

namespace Tests\Feature;

use App\Enums\AnomalySeverity;
use App\Enums\AnomalyStatus;
use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Enums\ShipmentStatus;
use App\Enums\UserRole;
use App\Facades\Analytics;
use App\Models\Anomaly;
use App\Models\ColdRoom;
use App\Models\Organization;
use App\Models\Shipment;
use App\Models\TemperatureRecord;
use App\Models\User;
use Database\Seeders\PlatformTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsPhase3Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformTaxonomySeeder::class);
    }

    public function test_kpi_dashboard_and_health_score(): void
    {
        $user = $this->actor();

        $dash = Analytics::dashboard();

        $this->assertArrayHasKey('health', $dash);
        $this->assertArrayHasKey('logistics', $dash);
        $this->assertArrayHasKey('cold_chain', $dash);
        $this->assertGreaterThanOrEqual(0, $dash['health']['score']);
        $this->assertLessThanOrEqual(100, $dash['health']['score']);
        $this->assertArrayHasKey('delivery', $dash['health']['pillars']);
        $this->assertArrayHasKey('methodology', $dash['health']);

        $this->actingAs($user)
            ->get(route('analytics.index'))
            ->assertRedirect(route('control-tower.index', ['tab' => 'intelligence']));

        $this->actingAs($user)
            ->get(route('control-tower.index', ['tab' => 'intelligence']))
            ->assertOk()
            ->assertSee('Health')
            ->assertSee('Intelligence');

        $token = $user->createToken('test')->plainTextToken;
        $this->withToken($token)
            ->getJson('/api/v1/analytics/kpis')
            ->assertOk()
            ->assertJsonPath('data.health.score', $dash['health']['score']);
    }

    public function test_anomaly_scan_detects_delay_and_temperature(): void
    {
        $user = $this->actor();
        $org = Organization::query()->create([
            'name' => 'DC Analytics',
            'slug' => 'dc-analytics-'.uniqid(),
            'type' => OrganizationType::Distributor,
            'status' => OrganizationStatus::Verified,
        ]);
        $org->users()->attach($user->id, ['is_primary' => true]);

        Shipment::query()->create([
            'code' => 'SH-2026-999001',
            'status' => ShipmentStatus::Delayed,
            'from_organization_id' => $org->id,
            'to_organization_id' => $org->id,
            'created_by' => $user->id,
            'eta_at' => now()->subHours(3),
            'dispatched_at' => now()->subHours(5),
            'unit' => 'kg',
            'load_kg' => 100,
        ]);

        $room = ColdRoom::query()->create([
            'organization_id' => $org->id,
            'name' => 'CF Analytics',
            'code' => 'CF-AN-'.random_int(100, 999),
            'type' => 'refrigerated',
            'status' => 'active',
            'capacity_kg' => 1000,
            'occupied_capacity_kg' => 920,
            'target_temp_min_c' => 2,
            'target_temp_max_c' => 6,
            'current_temperature_c' => 12,
            'responsible_user_id' => $user->id,
        ]);

        TemperatureRecord::query()->create([
            'cold_room_id' => $room->id,
            'temperature_c' => 12,
            'min_threshold_c' => 2,
            'max_threshold_c' => 6,
            'status' => 'critical',
            'source' => 'manual',
            'recorded_at' => now(),
            'recorded_by' => $user->id,
        ]);

        $created = Analytics::scanAnomalies($user);
        $this->assertNotEmpty($created);

        $this->assertDatabaseHas('anomalies', [
            'category' => 'delivery_delay',
            'status' => 'open',
        ]);
        $this->assertDatabaseHas('anomalies', [
            'category' => 'temperature_breach',
        ]);
        $this->assertDatabaseHas('anomalies', [
            'category' => 'capacity_anomaly',
        ]);

        $this->actingAs($user)
            ->get(route('alert-center.index'))
            ->assertOk()
            ->assertSee('Alert Center');

        $anomaly = Anomaly::query()->where('category', 'delivery_delay')->firstOrFail();

        $this->actingAs($user)
            ->patch(route('alert-center.status', $anomaly), [
                'status' => 'acknowledged',
                'note' => 'Vérification en cours',
            ])
            ->assertRedirect();

        $this->assertSame(AnomalyStatus::Acknowledged, $anomaly->fresh()->status);

        $token = $user->createToken('t')->plainTextToken;
        $this->withToken($token)
            ->getJson('/api/v1/alerts')
            ->assertOk()
            ->assertJsonStructure(['data' => ['kpis', 'feed']]);
    }

    public function test_health_score_weights_are_documented(): void
    {
        $health = Analytics::health();
        $weights = $health['methodology']['weights'];
        $this->assertSame(100, array_sum($weights));
        $this->assertSame(30, $weights['delivery']);
        $this->assertSame(25, $weights['cold_chain']);
    }

    private function actor(): User
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole(UserRole::Distributor);

        return $user;
    }
}
