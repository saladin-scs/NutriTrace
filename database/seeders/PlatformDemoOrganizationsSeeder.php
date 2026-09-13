<?php

namespace Database\Seeders;

use App\Actions\Organizations\CreateOrganizationAction;
use App\Actions\Organizations\VerifyOrganizationAction;
use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlatformDemoOrganizationsSeeder extends Seeder
{
    public function run(): void
    {
        $create = app(CreateOrganizationAction::class);
        $verify = app(VerifyOrganizationAction::class);

        $producer = User::query()->updateOrCreate(
            ['email' => 'producer@nutritrace.test'],
            [
                'name' => 'Producteur Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $producer->assignRole(UserRole::Producer);

        $collector = User::query()->updateOrCreate(
            ['email' => 'collector@nutritrace.test'],
            [
                'name' => 'Collecteur Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $collector->assignRole(UserRole::Collector);

        $farm = $create->execute($producer, [
            'name' => 'Ferme El Amal — Ariana',
            'type' => OrganizationType::Producer,
            'email' => 'ferme@nutritrace.test',
            'phone' => '+216 71 000 001',
            'city' => 'Ariana',
            'governorate' => 'Ariana',
            'address_line' => 'Route de Raoued',
            'description' => 'Exploitation maraîchère — Grand Tunis.',
        ]);
        $verify->execute(User::query()->where('email', 'admin@nutritrace.test')->first() ?? $producer, $farm, OrganizationStatus::Verified);

        $collectionOrg = $create->execute($collector, [
            'name' => 'GreenCollect Tunis',
            'type' => OrganizationType::Collector,
            'email' => 'collecte@nutritrace.test',
            'city' => 'Tunis',
            'governorate' => 'Tunis',
            'address_line' => 'Zone industrielle Charguia',
            'description' => 'Collecte de biodéchets et emballages.',
        ]);
        $verify->execute(User::query()->where('email', 'admin@nutritrace.test')->first() ?? $collector, $collectionOrg, OrganizationStatus::Verified);
    }
}
