<?php

namespace App\Domain\ColdChain;

use App\Enums\StockMovementType;
use App\Models\Batch;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

/**
 * Mass-balance for a batch: input vs distributed/stored/loss/waste.
 * Unexplained delta → anomaly flag for human verification.
 */
final class MassBalanceService
{
    /**
     * @return array<string, mixed>
     */
    public function forBatch(Batch $batch): array
    {
        $input = (float) $batch->quantity;

        $stored = (float) DB::table('storage_records')
            ->where('batch_id', $batch->id)
            ->whereIn('status', ['stored', 'partial'])
            ->sum('remaining_quantity');

        $movements = StockMovement::query()
            ->where('batch_id', $batch->id)
            ->get()
            ->groupBy(fn (StockMovement $m) => $m->type->value);

        $loss = (float) ($movements->get(StockMovementType::Loss->value)?->sum('quantity') ?? 0)
            + (float) ($movements->get(StockMovementType::Damage->value)?->sum('quantity') ?? 0)
            + (float) ($movements->get(StockMovementType::Expiration->value)?->sum('quantity') ?? 0);

        $distributed = (float) ($movements->get(StockMovementType::Shipment->value)?->sum('quantity') ?? 0)
            + (float) ($movements->get(StockMovementType::StockOut->value)?->sum('quantity') ?? 0)
            + (float) ($movements->get(StockMovementType::Sale->value)?->sum('quantity') ?? 0);

        // Avoid double-counting: stock_out from CF that feeds shipment already counted in storage remaining.
        $accounted = $stored + $distributed + $loss;
        $delta = round($input - $accounted, 3);

        return [
            'batch_code' => $batch->code,
            'input_kg' => $input,
            'stored_kg' => round($stored, 3),
            'distributed_kg' => round($distributed, 3),
            'loss_kg' => round($loss, 3),
            'accounted_kg' => round($accounted, 3),
            'unexplained_kg' => $delta,
            'balanced' => abs($delta) < 0.01,
            'anomaly' => abs($delta) >= 0.01 ? [
                'category' => 'MASS_BALANCE_ANOMALY',
                'severity' => abs($delta) > ($input * 0.05) ? 'critical' : 'warning',
                'message' => sprintf(
                    'Écart de masse %.3f kg non expliqué sur %s. Vérification humaine requise — pas une accusation automatique.',
                    abs($delta),
                    $batch->code
                ),
            ] : null,
        ];
    }
}
