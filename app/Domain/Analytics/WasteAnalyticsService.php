<?php

namespace App\Domain\Analytics;

use App\Enums\StockMovementType;
use App\Models\StockMovement;
use App\Models\Waste;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class WasteAnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->subDays(30)->startOfDay();
        $to ??= now();

        $lossQty = (float) StockMovement::query()
            ->whereIn('type', [
                StockMovementType::Loss->value,
                StockMovementType::Damage->value,
                StockMovementType::Expiration->value,
            ])
            ->whereBetween('occurred_at', [$from, $to])
            ->sum('quantity');

        $handledQty = (float) StockMovement::query()
            ->whereBetween('occurred_at', [$from, $to])
            ->sum('quantity');

        $wasteQty = 0.0;
        if (Schema::hasTable('wastes')) {
            $wasteQty = (float) Waste::query()
                ->whereBetween('created_at', [$from, $to])
                ->sum('quantity');
        }

        $loss = max($lossQty, $wasteQty);
        $lossRate = $handledQty > 0 ? round(($loss / $handledQty) * 100, 2) : 0.0;

        $recovered = 0.0;
        if (Schema::hasTable('valorization_processes') && Schema::hasColumn('valorization_processes', 'output_quantity')) {
            $recovered = (float) DB::table('valorization_processes')->sum('output_quantity');
        }

        $recoveryRate = $loss > 0 ? round(min(100, ($recovered / $loss) * 100), 1) : 100.0;

        return [
            'food_loss_kg' => round($loss, 3),
            'handled_kg' => round($handledQty, 3),
            'loss_rate_pct' => $lossRate,
            'recovery_rate_pct' => $recoveryRate,
            'expired_kg' => (float) StockMovement::query()
                ->where('type', StockMovementType::Expiration->value)
                ->whereBetween('occurred_at', [$from, $to])
                ->sum('quantity'),
            'damaged_kg' => (float) StockMovement::query()
                ->where('type', StockMovementType::Damage->value)
                ->whereBetween('occurred_at', [$from, $to])
                ->sum('quantity'),
        ];
    }

    public function performanceScore(?Carbon $from = null, ?Carbon $to = null): float
    {
        $s = $this->summary($from, $to);
        $lossRate = (float) $s['loss_rate_pct'];
        $score = 100 - min(100, $lossRate * 10);

        return max(0.0, min(100.0, $score));
    }
}
