<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\WasteCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlatformTaxonomySeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrateur', 'slug' => 'admin'],
            ['name' => 'Producteur', 'slug' => 'producer'],
            ['name' => 'Transformateur', 'slug' => 'transformer'],
            ['name' => 'Distributeur', 'slug' => 'distributor'],
            ['name' => 'Commerçant', 'slug' => 'retailer'],
            ['name' => 'Restaurant', 'slug' => 'restaurant'],
            ['name' => 'Consommateur', 'slug' => 'consumer'],
            ['name' => 'Collecteur', 'slug' => 'collector'],
            ['name' => 'Recycleur', 'slug' => 'recycler'],
            ['name' => 'Certificateur', 'slug' => 'certifier'],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['slug' => $role['slug']],
                ['name' => $role['name'], 'description' => 'Rôle plateforme NutriTrace']
            );
        }

        $permissions = [
            'organizations.manage',
            'products.manage',
            'batches.manage',
            'traceability.write',
            'certifications.manage',
            'certifications.verify',
            'waste.declare',
            'collections.manage',
            'collections.accept',
            'reports.view',
            'audit.view',
        ];

        foreach ($permissions as $slug) {
            Permission::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => Str::of($slug)->replace('.', ' ')->title()->toString(),
                    'description' => null,
                ]
            );
        }

        $admin = Role::query()->where('slug', 'admin')->firstOrFail();
        $admin->permissions()->sync(Permission::query()->pluck('id'));

        $wasteCategories = [
            ['name' => 'Biodéchet', 'slug' => 'biowaste', 'is_valorisable' => true],
            ['name' => 'Plastique', 'slug' => 'plastic', 'is_valorisable' => true],
            ['name' => 'Carton', 'slug' => 'cardboard', 'is_valorisable' => true],
            ['name' => 'Papier', 'slug' => 'paper', 'is_valorisable' => true],
            ['name' => 'Verre', 'slug' => 'glass', 'is_valorisable' => true],
            ['name' => 'Métal', 'slug' => 'metal', 'is_valorisable' => true],
            ['name' => 'Emballage composite', 'slug' => 'composite-packaging', 'is_valorisable' => false],
            ['name' => 'Déchet alimentaire', 'slug' => 'food-waste', 'is_valorisable' => true],
            ['name' => 'Non valorisable', 'slug' => 'non-valorisable', 'is_valorisable' => false],
        ];

        foreach ($wasteCategories as $category) {
            WasteCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                $category + ['description' => null]
            );
        }

        $productCategories = [
            'Fruits & légumes',
            'Produits laitiers',
            'Viandes & poissons',
            'Céréales & légumineuses',
            'Boissons',
            'Produits transformés',
        ];

        foreach ($productCategories as $index => $name) {
            ProductCategory::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
