<?php

namespace App\Actions\Products;

use App\Domain\Identity\AuditLogger;
use App\Models\Organization;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateProductAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $actor, Organization $organization, array $data): Product
    {
        return DB::transaction(function () use ($actor, $organization, $data) {
            $product = Product::query()->create([
                'organization_id' => $organization->id,
                'category_id' => $data['category_id'] ?? null,
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($organization->id, $data['name']),
                'sku' => $data['sku'] ?? null,
                'description' => $data['description'] ?? null,
                'unit' => $data['unit'] ?? 'kg',
                'packaging_type' => $data['packaging_type'] ?? null,
                'origin_country' => $data['origin_country'] ?? 'TN',
                'status' => $data['status'] ?? 'active',
            ]);

            $this->audit->log($actor, 'product.created', $product, null, [
                'name' => $product->name,
                'organization_id' => $organization->id,
            ]);

            return $product->fresh(['category', 'organization']);
        });
    }

    private function uniqueSlug(int $organizationId, string $name): string
    {
        $base = Str::slug($name) ?: 'produit';
        $slug = $base;
        $i = 1;

        while (Product::withTrashed()
            ->where('organization_id', $organizationId)
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
