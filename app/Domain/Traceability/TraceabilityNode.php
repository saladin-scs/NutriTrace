<?php

namespace App\Domain\Traceability;

use App\Enums\TraceabilityEventType;

/**
 * Représente un nœud de la chaîne Producteur → … → Consommateur.
 */
final class TraceabilityNode
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public readonly TraceabilityEventType $type,
        public readonly string $label,
        public readonly ?string $occurredAt,
        public readonly ?string $organizationName = null,
        public readonly ?string $locationLabel = null,
        public readonly array $meta = [],
    ) {}
}
