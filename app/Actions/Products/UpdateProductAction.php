<?php

namespace App\Actions\Products;

use App\Domain\Identity\AuditLogger;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateProductAction
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $actor, Product $product, array $data): Product
    {
        return DB::transaction(function () use ($actor, $product, $data) {
            $old = $product->only(['name', 'sku', 'description', 'unit', 'packaging_type', 'origin_country', 'status', 'category_id']);

            $product->update(collect($data)->only([
                'name', 'sku', 'description', 'unit', 'packaging_type',
                'origin_country', 'status', 'category_id',
            ])->all());

            $this->audit->log($actor, 'product.updated', $product, $old, $product->fresh()->only([
                'name', 'sku', 'description', 'unit', 'packaging_type', 'origin_country', 'status', 'category_id',
            ]));

            return $product->fresh(['category', 'organization']);
        });
    }
}
