<?php

namespace Tests\Feature;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Enums\UserRole;
use App\Models\Batch;
use App\Models\Organization;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PlatformTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraceabilitySprint3Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformTaxonomySeeder::class);
    }

    public function test_producer_can_create_product_and_batch_with_qr_code(): void
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::Producer);

        $org = Organization::query()->create([
            'name' => 'Ferme Test',
            'slug' => 'ferme-test',
            'type' => OrganizationType::Producer,
            'status' => OrganizationStatus::Verified,
        ]);
        $org->users()->attach($user->id, ['is_primary' => true]);

        $this->actingAs($user)->post(route('products.store'), [
            'organization_id' => $org->id,
            'name' => 'Olives',
            'unit' => 'kg',
        ])->assertRedirect();

        $product = Product::query()->where('name', 'Olives')->firstOrFail();

        $this->actingAs($user)->post(route('products.batches.store', $product), [
            'quantity' => 120,
            'unit' => 'kg',
        ])->assertRedirect();

        $batch = Batch::query()->where('product_id', $product->id)->firstOrFail();
        $this->assertMatchesRegularExpression('/^NT-\d{4}-\d{6}$/', $batch->code);
        $this->assertDatabaseHas('traceability_events', [
            'batch_id' => $batch->id,
            'type' => 'production',
        ]);

        $this->get(route('trace.show', $batch->code))
            ->assertOk()
            ->assertSee($batch->code)
            ->assertSee('Olives');
    }

    public function test_transformation_creates_output_batch_linked_to_parent(): void
    {
        $user = User::factory()->create();
        $user->assignRole(UserRole::Transformer);

        $org = Organization::query()->create([
            'name' => 'Atelier',
            'slug' => 'atelier',
            'type' => OrganizationType::Transformer,
            'status' => OrganizationStatus::Verified,
        ]);
        $org->users()->attach($user->id, ['is_primary' => true]);

        $product = Product::query()->create([
            'organization_id' => $org->id,
            'name' => 'Pâte',
            'slug' => 'pate',
            'unit' => 'kg',
            'status' => 'active',
        ]);

        $batch = Batch::query()->create([
            'product_id' => $product->id,
            'organization_id' => $org->id,
            'code' => 'NT-2026-000001',
            'status' => 'active',
            'quantity' => 100,
            'unit' => 'kg',
            'produced_at' => now(),
        ]);

        $this->actingAs($user)->post(route('batches.transform', $batch), [
            'organization_id' => $org->id,
            'process_name' => 'Broyage',
            'output_quantity' => 90,
            'loss_quantity' => 10,
        ])->assertRedirect();

        $output = Batch::query()->where('parent_batch_id', $batch->id)->first();
        $this->assertNotNull($output);
        $this->assertSame('transformed', $batch->fresh()->status->value);
        $this->assertDatabaseHas('transformations', [
            'input_batch_id' => $batch->id,
            'output_batch_id' => $output->id,
        ]);
    }

    public function test_public_api_traceability_endpoint(): void
    {
        $org = Organization::query()->create([
            'name' => 'Org',
            'slug' => 'org',
            'type' => OrganizationType::Producer,
            'status' => OrganizationStatus::Verified,
        ]);
        $product = Product::query()->create([
            'organization_id' => $org->id,
            'name' => 'Figues',
            'slug' => 'figues',
            'unit' => 'kg',
            'status' => 'active',
        ]);
        $batch = Batch::query()->create([
            'product_id' => $product->id,
            'organization_id' => $org->id,
            'code' => 'NT-2026-000042',
            'status' => 'active',
            'quantity' => 10,
            'unit' => 'kg',
            'produced_at' => now(),
        ]);

        $this->getJson('/api/v1/traceability/NT-2026-000042')
            ->assertOk()
            ->assertJsonPath('data.code', 'NT-2026-000042')
            ->assertJsonPath('data.product.name', 'Figues');
    }
}
