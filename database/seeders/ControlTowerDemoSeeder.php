<?php

namespace Database\Seeders;

use App\Actions\Batches\CreateBatchAction;
use App\Actions\Organizations\CreateOrganizationAction;
use App\Actions\Organizations\VerifyOrganizationAction;
use App\Actions\Products\CreateProductAction;
use App\Actions\Shipments\CreateShipmentAction;
use App\Actions\Shipments\DispatchShipmentAction;
use App\Actions\Shipments\ReceiveShipmentAction;
use App\Domain\Distribution\RouteCodeAllocator;
use App\Enums\DistributionChannelType;
use App\Enums\DistributionNodeType;
use App\Enums\FuelType;
use App\Enums\NetworkEntityStatus;
use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Enums\RouteStatus;
use App\Enums\ShipmentStatus;
use App\Enums\TransportMode;
use App\Enums\UserRole;
use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Models\ColdRoom;
use App\Models\DistributionChannel;
use App\Models\DistributionLink;
use App\Models\DistributionNode;
use App\Models\Organization;
use App\Models\ProductCategory;
use App\Models\Route as LogisticsRoute;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehiclePosition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ControlTowerDemoSeeder extends Seeder
{
    public function run(): void
    {
        $createOrg = app(CreateOrganizationAction::class);
        $verify = app(VerifyOrganizationAction::class);
        $createProduct = app(CreateProductAction::class);
        $createBatch = app(CreateBatchAction::class);
        $createShipment = app(CreateShipmentAction::class);
        $dispatch = app(DispatchShipmentAction::class);
        $receive = app(ReceiveShipmentAction::class);
        $routeCodes = app(RouteCodeAllocator::class);

        $admin = User::query()->where('email', 'admin@nutritrace.test')->firstOrFail();

        $logistics = User::query()->updateOrCreate(
            ['email' => 'logistics@nutritrace.test'],
            [
                'name' => 'Responsable Logistique',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $logistics->assignRole(UserRole::Distributor);

        $actors = [
            'producer' => $this->org($createOrg, $verify, $admin, [
                'email' => 'bizerte-farm@nutritrace.test',
                'name' => 'Ferme Bleu Bizerte',
                'type' => OrganizationType::Producer,
                'city' => 'Bizerte',
                'governorate' => 'Bizerte',
                'address_line' => 'Route de Menzel Jemil',
                'lat' => 37.2744,
                'lng' => 9.8739,
                'user_email' => 'ct-producer@nutritrace.test',
                'user_name' => 'Producteur Bizerte',
                'role' => UserRole::Producer,
            ]),
            'dc' => $this->org($createOrg, $verify, $admin, [
                'email' => 'dc-tunis@nutritrace.test',
                'name' => 'Centre Distribution Tunis',
                'type' => OrganizationType::Distributor,
                'city' => 'Tunis',
                'governorate' => 'Tunis',
                'address_line' => 'Zone industrielle Charguia',
                'lat' => 36.8481,
                'lng' => 10.2040,
                'user_email' => 'ct-dc@nutritrace.test',
                'user_name' => 'Ops DC Tunis',
                'role' => UserRole::Distributor,
            ]),
            'wholesaler' => $this->org($createOrg, $verify, $admin, [
                'email' => 'grossiste-sousse@nutritrace.test',
                'name' => 'Grossiste Sahel Sousse',
                'type' => OrganizationType::Wholesaler,
                'city' => 'Sousse',
                'governorate' => 'Sousse',
                'address_line' => 'Marché de gros',
                'lat' => 35.8256,
                'lng' => 10.6411,
                'user_email' => 'ct-wholesaler@nutritrace.test',
                'user_name' => 'Grossiste Sousse',
                'role' => UserRole::Distributor,
            ]),
            'retailer' => $this->org($createOrg, $verify, $admin, [
                'email' => 'retail-sfax@nutritrace.test',
                'name' => 'Supermarché Sfax Centre',
                'type' => OrganizationType::Retailer,
                'city' => 'Sfax',
                'governorate' => 'Sfax',
                'address_line' => 'Avenue Habib Bourguiba',
                'lat' => 34.7398,
                'lng' => 10.7600,
                'user_email' => 'ct-retailer@nutritrace.test',
                'user_name' => 'Retail Sfax',
                'role' => UserRole::Retailer,
            ]),
            'restaurant' => $this->org($createOrg, $verify, $admin, [
                'email' => 'resto-tunis@nutritrace.test',
                'name' => 'Restaurant Dar El Jeld',
                'type' => OrganizationType::Restaurant,
                'city' => 'Tunis',
                'governorate' => 'Tunis',
                'address_line' => 'Médina',
                'lat' => 36.7990,
                'lng' => 10.1710,
                'user_email' => 'ct-resto@nutritrace.test',
                'user_name' => 'Resto Tunis',
                'role' => UserRole::Retailer,
            ]),
        ];

        if (! $logistics->belongsToOrganization($actors['dc'])) {
            $actors['dc']->users()->attach($logistics->id, ['is_primary' => false]);
        }

        $coldRoom = ColdRoom::query()->firstOrCreate(
            ['code' => 'CF-TUNIS-01'],
            [
                'organization_id' => $actors['dc']->id,
                'owner_organization_id' => $actors['dc']->id,
                'location_id' => $actors['dc']->primary_location_id,
                'responsible_user_id' => $logistics->id,
                'name' => 'Chambre froide DC Tunis',
                'type' => 'refrigerated',
                'status' => 'active',
                'capacity_kg' => 12000,
                'occupied_capacity_kg' => 0,
                'target_temp_min_c' => 2,
                'target_temp_max_c' => 6,
                'current_temperature_c' => 4.3,
                'energy_kwh_day' => 210,
            ]
        );

        $channel = DistributionChannel::query()->updateOrCreate(
            ['code' => 'SHORT-TN-01'],
            [
                'name' => 'Circuit court Grand Tunis → Sahel',
                'type' => DistributionChannelType::Short,
                'status' => NetworkEntityStatus::Active,
                'description' => 'Canal court producteur → DC → grossiste / retail',
            ]
        );

        $nodes = [
            'producer' => $this->node($channel, $actors['producer'], DistributionNodeType::Producer, 'NODE-BIZ', 37.2744, 9.8739),
            'dc' => $this->node($channel, $actors['dc'], DistributionNodeType::DistributionCenter, 'NODE-DC-TUN', 36.8481, 10.2040),
            'cold' => DistributionNode::query()->updateOrCreate(
                ['distribution_channel_id' => $channel->id, 'code' => 'NODE-CF-TUN'],
                [
                    'organization_id' => $actors['dc']->id,
                    'location_id' => $actors['dc']->primary_location_id,
                    'cold_room_id' => $coldRoom->id,
                    'node_type' => DistributionNodeType::ColdRoom,
                    'name' => 'CF Tunis DC',
                    'status' => NetworkEntityStatus::Active,
                    'latitude' => 36.8495,
                    'longitude' => 10.2055,
                ]
            ),
            'wholesaler' => $this->node($channel, $actors['wholesaler'], DistributionNodeType::Wholesaler, 'NODE-SOU', 35.8256, 10.6411),
            'retailer' => $this->node($channel, $actors['retailer'], DistributionNodeType::Retailer, 'NODE-SFX', 34.7398, 10.7600),
            'restaurant' => $this->node($channel, $actors['restaurant'], DistributionNodeType::Restaurant, 'NODE-REST', 36.7990, 10.1710),
        ];

        $this->link($channel, $nodes['producer'], $nodes['dc'], 65, 75);
        $this->link($channel, $nodes['dc'], $nodes['cold'], 1, 10);
        $this->link($channel, $nodes['dc'], $nodes['wholesaler'], 140, 120);
        $this->link($channel, $nodes['wholesaler'], $nodes['retailer'], 130, 110);
        $this->link($channel, $nodes['dc'], $nodes['restaurant'], 12, 35);

        $v1 = Vehicle::query()->updateOrCreate(
            ['registration' => 'TN-1845-A'],
            [
                'organization_id' => $actors['dc']->id,
                'type' => VehicleType::RefrigeratedTruck,
                'fuel_type' => FuelType::Diesel,
                'status' => VehicleStatus::InTransit,
                'capacity_kg' => 8000,
                'emission_factor' => 0.92,
                'driver_name' => 'Mohamed Ben Ali',
            ]
        );

        $v2 = Vehicle::query()->updateOrCreate(
            ['registration' => 'TN-2201-B'],
            [
                'organization_id' => $actors['dc']->id,
                'type' => VehicleType::Van,
                'fuel_type' => FuelType::Diesel,
                'status' => VehicleStatus::Available,
                'capacity_kg' => 2500,
                'emission_factor' => 0.71,
                'driver_name' => 'Sami Trabelsi',
            ]
        );

        $routeSousse = LogisticsRoute::query()->updateOrCreate(
            ['code' => 'RT-2026-000001'],
            [
                'name' => 'Tunis → Zaghouan → Kairouan → Sousse',
                'origin_node_id' => $nodes['dc']->id,
                'destination_node_id' => $nodes['wholesaler']->id,
                'origin_location_id' => $actors['dc']->primary_location_id,
                'destination_location_id' => $actors['wholesaler']->primary_location_id,
                'distance_km' => 148,
                'estimated_duration_min' => 130,
                'stops_count' => 2,
                'status' => RouteStatus::Active,
                'waypoints' => [
                    ['lat' => 36.4020, 'lng' => 10.1430, 'label' => 'Zaghouan'],
                    ['lat' => 35.6781, 'lng' => 10.0963, 'label' => 'Kairouan'],
                ],
                'estimated_co2e_kg' => 120,
            ]
        );

        $routeSfax = LogisticsRoute::query()->updateOrCreate(
            ['code' => 'RT-2026-000002'],
            [
                'name' => 'Sousse → Sfax',
                'origin_node_id' => $nodes['wholesaler']->id,
                'destination_node_id' => $nodes['retailer']->id,
                'distance_km' => 132,
                'estimated_duration_min' => 115,
                'stops_count' => 0,
                'status' => RouteStatus::Completed,
                'waypoints' => [],
                'estimated_co2e_kg' => 95,
            ]
        );

        $routeResto = LogisticsRoute::query()->updateOrCreate(
            ['code' => $routeCodes->allocate()],
            [
                'name' => 'DC Tunis → Médina',
                'origin_node_id' => $nodes['dc']->id,
                'destination_node_id' => $nodes['restaurant']->id,
                'distance_km' => 14,
                'estimated_duration_min' => 40,
                'stops_count' => 0,
                'status' => RouteStatus::Delayed,
                'waypoints' => [],
            ]
        );

        $category = ProductCategory::query()->first();
        $product = $createProduct->execute($admin, $actors['producer'], [
            'name' => 'Tomates cerises Bizerte',
            'unit' => 'kg',
            'category_id' => $category?->id,
        ]);

        $batchActive = $createBatch->execute($admin, $product, [
            'quantity' => 420,
            'unit' => 'kg',
            'produced_at' => now()->subDay(),
            'expires_at' => now()->addDays(8),
        ]);

        $batchDone = $createBatch->execute($admin, $product, [
            'quantity' => 300,
            'unit' => 'kg',
            'produced_at' => now()->subDays(3),
            'expires_at' => now()->addDays(5),
        ]);

        $batchDelayed = $createBatch->execute($admin, $product, [
            'quantity' => 180,
            'unit' => 'kg',
            'produced_at' => now()->subHours(20),
            'expires_at' => now()->addDays(6),
        ]);

        // Active in-transit Tunis → Sousse
        $s1 = $createShipment->execute($logistics, [
            'distribution_channel_id' => $channel->id,
            'vehicle_id' => $v1->id,
            'route_id' => $routeSousse->id,
            'from_organization_id' => $actors['dc']->id,
            'to_organization_id' => $actors['wholesaler']->id,
            'from_location_id' => $actors['dc']->primary_location_id,
            'to_location_id' => $actors['wholesaler']->primary_location_id,
            'origin_node_id' => $nodes['dc']->id,
            'destination_node_id' => $nodes['wholesaler']->id,
            'eta_at' => now()->addHours(2),
            'current_temperature_c' => 4.8,
            'load_kg' => 420,
        ], [['batch_id' => $batchActive->id, 'quantity' => 420]]);
        $dispatch->execute($logistics, $s1, ['current_temperature_c' => 4.8]);

        VehiclePosition::query()->create([
            'vehicle_id' => $v1->id,
            'shipment_id' => $s1->id,
            'latitude' => 36.25,
            'longitude' => 10.18,
            'speed_kmh' => 72,
            'heading' => 165,
            'temperature_c' => 4.8,
            'recorded_at' => now()->subMinutes(12),
        ]);

        // Completed Sousse → Sfax
        $s2 = $createShipment->execute($logistics, [
            'distribution_channel_id' => $channel->id,
            'vehicle_id' => $v2->id,
            'route_id' => $routeSfax->id,
            'from_organization_id' => $actors['wholesaler']->id,
            'to_organization_id' => $actors['retailer']->id,
            'from_location_id' => $actors['wholesaler']->primary_location_id,
            'to_location_id' => $actors['retailer']->primary_location_id,
            'origin_node_id' => $nodes['wholesaler']->id,
            'destination_node_id' => $nodes['retailer']->id,
            'eta_at' => now()->subHours(3),
            'current_temperature_c' => 5.1,
            'load_kg' => 300,
        ], [['batch_id' => $batchDone->id, 'quantity' => 300]]);
        $dispatch->execute($logistics, $s2);
        $receive->execute($logistics, $s2->fresh(), ['current_temperature_c' => 5.2]);

        // Delayed DC → restaurant
        $s3 = $createShipment->execute($logistics, [
            'distribution_channel_id' => $channel->id,
            'vehicle_id' => $v2->id,
            'route_id' => $routeResto->id,
            'from_organization_id' => $actors['dc']->id,
            'to_organization_id' => $actors['restaurant']->id,
            'from_location_id' => $actors['dc']->primary_location_id,
            'to_location_id' => $actors['restaurant']->primary_location_id,
            'origin_node_id' => $nodes['dc']->id,
            'destination_node_id' => $nodes['restaurant']->id,
            'eta_at' => now()->subMinutes(40),
            'current_temperature_c' => 6.4,
            'load_kg' => 180,
        ], [['batch_id' => $batchDelayed->id, 'quantity' => 180]]);
        $dispatch->execute($logistics, $s3, ['delayed' => true, 'current_temperature_c' => 6.4]);
        $s3->fresh()->update(['status' => ShipmentStatus::Delayed]);
        $v2->update(['status' => VehicleStatus::InTransit]);

        VehiclePosition::query()->create([
            'vehicle_id' => $v2->id,
            'shipment_id' => $s3->id,
            'latitude' => 36.82,
            'longitude' => 10.19,
            'speed_kmh' => 18,
            'heading' => 210,
            'temperature_c' => 6.4,
            'recorded_at' => now()->subMinutes(5),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function org(
        CreateOrganizationAction $createOrg,
        VerifyOrganizationAction $verify,
        User $admin,
        array $data,
    ): Organization {
        $user = User::query()->updateOrCreate(
            ['email' => $data['user_email']],
            [
                'name' => $data['user_name'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $user->assignRole($data['role']);

        $org = Organization::query()->where('email', $data['email'])->first()
            ?? $createOrg->execute($user, [
                'name' => $data['name'],
                'type' => $data['type'],
                'email' => $data['email'],
                'city' => $data['city'],
                'governorate' => $data['governorate'],
                'address_line' => $data['address_line'],
            ]);

        if ($org->status !== OrganizationStatus::Verified) {
            $verify->execute($admin, $org, OrganizationStatus::Verified);
        }

        if ($org->primaryLocation) {
            $org->primaryLocation->update([
                'latitude' => $data['lat'],
                'longitude' => $data['lng'],
            ]);
        }

        return $org->fresh(['primaryLocation']);
    }

    private function node(
        DistributionChannel $channel,
        Organization $org,
        DistributionNodeType $type,
        string $code,
        float $lat,
        float $lng,
    ): DistributionNode {
        return DistributionNode::query()->updateOrCreate(
            ['distribution_channel_id' => $channel->id, 'code' => $code],
            [
                'organization_id' => $org->id,
                'location_id' => $org->primary_location_id,
                'node_type' => $type,
                'name' => $org->name,
                'status' => NetworkEntityStatus::Active,
                'latitude' => $lat,
                'longitude' => $lng,
            ]
        );
    }

    private function link(
        DistributionChannel $channel,
        DistributionNode $from,
        DistributionNode $to,
        float $km,
        int $minutes,
    ): void {
        DistributionLink::query()->updateOrCreate(
            [
                'distribution_channel_id' => $channel->id,
                'from_node_id' => $from->id,
                'to_node_id' => $to->id,
            ],
            [
                'distance_km' => $km,
                'estimated_duration_min' => $minutes,
                'transport_mode' => TransportMode::RefrigeratedRoad,
                'capacity_kg' => 8000,
                'status' => NetworkEntityStatus::Active,
                'environmental_impact_kg_co2e' => round($km * 0.85, 2),
            ]
        );
    }
}
