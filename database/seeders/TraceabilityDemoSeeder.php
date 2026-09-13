<?php

namespace Database\Seeders;

use App\Actions\Batches\CreateBatchAction;
use App\Actions\Batches\RecordDistributionAction;
use App\Actions\Batches\RecordTransformationAction;
use App\Actions\Organizations\CreateOrganizationAction;
use App\Actions\Organizations\VerifyOrganizationAction;
use App\Actions\Products\CreateProductAction;
use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Enums\TransportMode;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TraceabilityDemoSeeder extends Seeder
{
    public function run(): void
    {
        $createOrg = app(CreateOrganizationAction::class);
        $verify = app(VerifyOrganizationAction::class);
        $createProduct = app(CreateProductAction::class);
        $createBatch = app(CreateBatchAction::class);
        $transform = app(RecordTransformationAction::class);
        $distribute = app(RecordDistributionAction::class);

        $admin = User::query()->where('email', 'admin@nutritrace.test')->first();

        $producer = User::query()->updateOrCreate(
            ['email' => 'producer@nutritrace.test'],
            [
                'name' => 'Producteur Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $producer->assignRole(UserRole::Producer);

        $transformerUser = User::query()->updateOrCreate(
            ['email' => 'transformer@nutritrace.test'],
            [
                'name' => 'Transformateur Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $transformerUser->assignRole(UserRole::Transformer);

        $retailerUser = User::query()->updateOrCreate(
            ['email' => 'retailer@nutritrace.test'],
            [
                'name' => 'Commerçant Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $retailerUser->assignRole(UserRole::Retailer);

        $farm = Organization::query()->where('slug', 'ferme-el-amal-ariana')->first()
            ?? $createOrg->execute($producer, [
                'name' => 'Ferme El Amal — Ariana',
                'type' => OrganizationType::Producer,
                'email' => 'ferme@nutritrace.test',
                'city' => 'Ariana',
                'governorate' => 'Ariana',
                'address_line' => 'Route de Raoued',
            ]);

        if ($farm->status !== OrganizationStatus::Verified && $admin) {
            $verify->execute($admin, $farm, OrganizationStatus::Verified);
        }

        if (! $producer->belongsToOrganization($farm)) {
            $farm->users()->attach($producer->id, ['is_primary' => true]);
        }

        $plant = Organization::query()->where('slug', 'like', 'atelier-vert%')->first()
            ?? $createOrg->execute($transformerUser, [
                'name' => 'Atelier Vert La Marsa',
                'type' => OrganizationType::Transformer,
                'email' => 'atelier@nutritrace.test',
                'city' => 'La Marsa',
                'governorate' => 'Tunis',
                'address_line' => 'Zone artisanale',
            ]);
        if ($plant->status !== OrganizationStatus::Verified && $admin) {
            $verify->execute($admin, $plant, OrganizationStatus::Verified);
        }

        $shop = Organization::query()->where('slug', 'like', 'marche-local%')->first()
            ?? $createOrg->execute($retailerUser, [
                'name' => 'Marché Local Lac 2',
                'type' => OrganizationType::Retailer,
                'email' => 'marche@nutritrace.test',
                'city' => 'Tunis',
                'governorate' => 'Tunis',
                'address_line' => 'Les Berges du Lac',
            ]);
        if ($shop->status !== OrganizationStatus::Verified && $admin) {
            $verify->execute($admin, $shop, OrganizationStatus::Verified);
        }

        $category = ProductCategory::query()->where('slug', 'fruits-legumes')->first()
            ?? ProductCategory::query()->first();

        $tomatoes = $farm->products()->where('slug', 'tomates-cerises')->first()
            ?? $createProduct->execute($producer, $farm, [
                'name' => 'Tomates cerises',
                'category_id' => $category?->id,
                'unit' => 'kg',
                'packaging_type' => 'caisse bois',
                'description' => 'Tomates cerises cultivées à Ariana.',
            ]);

        if ($tomatoes->batches()->exists()) {
            return;
        }

        $rawBatch = $createBatch->execute($producer, $tomatoes, [
            'quantity' => 500,
            'unit' => 'kg',
            'produced_at' => now()->subDays(3),
            'expires_at' => now()->addDays(10),
            'notes' => 'Récolte matin',
        ]);

        $transformation = $transform->execute($transformerUser, $rawBatch, $plant, [
            'process_name' => 'Tri et conditionnement',
            'output_quantity' => 480,
            'loss_quantity' => 20,
            'occurred_at' => now()->subDays(2),
        ]);

        $distribute->execute($transformerUser, $transformation->outputBatch, $plant, $shop, [
            'quantity' => 480,
            'distance_km' => 22,
            'transport_mode' => TransportMode::RefrigeratedRoad,
            'shipped_at' => now()->subDay(),
            'received_at' => now()->subDay()->addHours(2),
        ]);
    }
}
