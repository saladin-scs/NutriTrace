<?php

namespace Tests\Feature;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Enums\UserRole;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\PlatformTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformTaxonomySeeder::class);
    }

    public function test_user_can_create_pending_organization(): void
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::Producer);

        $response = $this->actingAs($user)->post(route('organizations.store'), [
            'name' => 'Ferme Test',
            'type' => OrganizationType::Producer->value,
            'city' => 'Tunis',
            'governorate' => 'Tunis',
        ]);

        $organization = Organization::query()->where('name', 'Ferme Test')->first();

        $this->assertNotNull($organization);
        $this->assertSame(OrganizationStatus::Pending, $organization->status);
        $this->assertTrue($user->isPrimaryMemberOf($organization));
        $response->assertRedirect(route('organizations.show', $organization));
    }

    public function test_non_member_cannot_view_organization(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $organization = Organization::query()->create([
            'name' => 'Privée',
            'slug' => 'privee',
            'type' => OrganizationType::Retailer,
            'status' => OrganizationStatus::Pending,
        ]);
        $organization->users()->attach($owner->id, ['is_primary' => true]);

        $this->actingAs($stranger)
            ->get(route('organizations.show', $organization))
            ->assertForbidden();
    }

    public function test_admin_can_verify_organization(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->create();

        $organization = Organization::query()->create([
            'name' => 'À vérifier',
            'slug' => 'a-verifier',
            'type' => OrganizationType::Collector,
            'status' => OrganizationStatus::Pending,
        ]);
        $organization->users()->attach($owner->id, ['is_primary' => true]);

        $this->actingAs($admin)
            ->post(route('admin.organizations.verify', $organization), [
                'status' => OrganizationStatus::Verified->value,
            ])
            ->assertRedirect();

        $this->assertSame(OrganizationStatus::Verified, $organization->fresh()->status);
        $this->assertSame($admin->id, $organization->fresh()->verified_by);
    }

    public function test_admin_can_assign_platform_role(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.role', $user), [
                'role' => UserRole::Collector->value,
            ])
            ->assertRedirect();

        $this->assertSame(UserRole::Collector, $user->fresh()->role);
    }

    public function test_api_can_list_member_organizations(): void
    {
        $user = User::factory()->create();
        $organization = Organization::query()->create([
            'name' => 'API Org',
            'slug' => 'api-org',
            'type' => OrganizationType::Distributor,
            'status' => OrganizationStatus::Verified,
        ]);
        $organization->users()->attach($user->id, ['is_primary' => true]);

        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/organizations')
            ->assertOk()
            ->assertJsonFragment(['name' => 'API Org']);
    }
}
