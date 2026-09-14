<?php

namespace App\Domain\Analytics;

use App\Domain\ColdChain\MassBalanceService;
use App\Models\Batch;

/**
 * Thin bridge so Analytics depends on ColdChain mass-balance without circular facade use.
 */
final class MassBalanceBridge
{
    public function __construct(private MassBalanceService $inner) {}

    /**
     * @return array<string, mixed>
     */
    public function forBatch(Batch $batch): array
    {
        return $this->inner->forBatch($batch);
    }
}
