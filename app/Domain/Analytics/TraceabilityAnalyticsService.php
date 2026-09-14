<?php

namespace App\Domain\Analytics;

use App\Models\Batch;
use App\Models\TraceabilityEvent;
use Illuminate\Support\Carbon;

final class TraceabilityAnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->subDays(30)->startOfDay();
        $to ??= now();

        $batches = Batch::query()->whereBetween('created_at', [$from, $to])->count();
        $withEvents = Batch::query()
            ->whereBetween('created_at', [$from, $to])
            ->whereHas('traceabilityEvents')
            ->count();

        $coverage = $batches > 0 ? round(($withEvents / $batches) * 100, 1) : 100.0;

        $eventsTotal = TraceabilityEvent::query()
            ->whereBetween('occurred_at', [$from, $to])
            ->count();

        return [
            'batches_in_period' => $batches,
            'batches_with_events' => $withEvents,
            'traceability_coverage_pct' => $coverage,
            'avg_events_per_batch' => $batches > 0 ? round($eventsTotal / $batches, 1) : 0,
            'events_total' => $eventsTotal,
        ];
    }

    public function coverageScore(?Carbon $from = null, ?Carbon $to = null): float
    {
        return (float) $this->summary($from, $to)['traceability_coverage_pct'];
    }
}
