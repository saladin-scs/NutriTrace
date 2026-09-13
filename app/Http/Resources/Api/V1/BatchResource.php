<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\Product\QrPassportUrl;
use App\Domain\Traceability\TraceabilityChainBuilder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Batch */
class BatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $qr = app(QrPassportUrl::class);
        $chain = app(TraceabilityChainBuilder::class);

        return [
            'id' => $this->id,
            'code' => $this->code,
            'status' => $this->status?->value,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'produced_at' => $this->produced_at,
            'expires_at' => $this->expires_at,
            'passport_url' => $qr->forCode($this->code),
            'qr_image_url' => $qr->qrImageUrl($this->code),
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'origin_country' => $this->product->origin_country,
            ]),
            'organization' => $this->whenLoaded('organization', fn () => [
                'id' => $this->organization->id,
                'name' => $this->organization->name,
            ]),
            'traceability' => $this->when(
                $this->relationLoaded('traceabilityEvents'),
                fn () => collect($chain->build($this->resource))->map(fn ($node) => [
                    'type' => $node->type->value,
                    'label' => $node->label,
                    'occurred_at' => $node->occurredAt,
                    'organization' => $node->organizationName,
                    'location' => $node->locationLabel,
                ])->all()
            ),
            'created_at' => $this->created_at,
        ];
    }
}
