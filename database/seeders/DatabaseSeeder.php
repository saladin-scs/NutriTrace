<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PlatformTaxonomySeeder::class);

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@nutritrace.test'],
            [
                'name' => 'Admin NutriTrace',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole(UserRole::Admin);

        $user = User::query()->updateOrCreate(
            ['email' => 'user@nutritrace.test'],
            [
                'name' => 'Consommateur Demo',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $user->assignRole(UserRole::Consumer);

        $this->call(PlatformDemoOrganizationsSeeder::class);
        $this->call(TraceabilityDemoSeeder::class);
        $this->call(ColdRoomDemoSeeder::class);
        $this->call(ControlTowerDemoSeeder::class);
    }
}
