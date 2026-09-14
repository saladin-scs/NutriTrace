<?php

namespace App\Domain\Analytics;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class DistributionAnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->subDays(30)->startOfDay();
        $to ??= now();

        $base = Shipment::query()
            ->whereBetween('created_at', [$from, $to]);

        $total = (clone $base)->count();
        $active = Shipment::query()->whereIn('status', [
            ShipmentStatus::Dispatched,
            ShipmentStatus::InTransit,
            ShipmentStatus::Arrived,
            ShipmentStatus::Delayed,
        ])->count();
        $completed = (clone $base)->where('status', ShipmentStatus::Delivered)->count();
        $delayed = Shipment::query()->where('status', ShipmentStatus::Delayed)->count();
        $cancelled = (clone $base)->where('status', ShipmentStatus::Cancelled)->count();

        $delivered = Shipment::query()
            ->where('status', ShipmentStatus::Delivered)
            ->whereNotNull('dispatched_at')
            ->whereNotNull('delivered_at')
            ->whereBetween('delivered_at', [$from, $to])
            ->get(['dispatched_at', 'delivered_at', 'eta_at']);

        $durations = $delivered->map(fn (Shipment $s) => $s->dispatched_at->diffInMinutes($s->delivered_at));
        $avgDeliveryMin = $durations->avg() ?? 0;

        $onTime = $delivered->filter(function (Shipment $s) {
            if (! $s->eta_at) {
                return true;
            }

            return $s->delivered_at->lte($s->eta_at->copy()->addMinutes(30));
        })->count();

        $onTimeRate = $delivered->count() > 0
            ? round(($onTime / $delivered->count()) * 100, 1)
            : 100.0;

        $vehicleUtil = $this->vehicleUtilization();

        return [
            'period' => [
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
            ],
            'total_shipments' => $total,
            'active_shipments' => $active,
            'completed_shipments' => $completed,
            'delayed_shipments' => $delayed,
            'cancelled_shipments' => $cancelled,
            'on_time_delivery_rate' => $onTimeRate,
            'average_delivery_minutes' => (int) round($avgDeliveryMin),
            'average_delivery_label' => $this->formatMinutes((int) round($avgDeliveryMin)),
            'vehicle_utilization_rate' => $vehicleUtil,
            'deliveries_today' => Shipment::query()
                ->where('status', ShipmentStatus::Delivered)
                ->whereDate('delivered_at', Carbon::today())
                ->count(),
        ];
    }

    /**
     * Delivery pillar score 0–100 for Health Score.
     */
    public function performanceScore(?Carbon $from = null, ?Carbon $to = null): float
    {
        $s = $this->summary($from, $to);
        $onTime = (float) $s['on_time_delivery_rate'];
        $delayPenalty = min(40.0, (float) $s['delayed_shipments'] * 8);

        return max(0.0, min(100.0, ($onTime * 0.7) + (30 - min(30, $delayPenalty))));
    }

    private function vehicleUtilization(): float
    {
        $total = DB::table('vehicles')->whereNull('deleted_at')->count();
        if ($total === 0) {
            return 0.0;
        }

        $busy = DB::table('vehicles')
            ->whereNull('deleted_at')
            ->where('status', 'in_transit')
            ->count();

        return round(($busy / $total) * 100, 1);
    }

    private function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return '—';
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return $h > 0 ? sprintf('%dh %02dm', $h, $m) : sprintf('%dm', $m);
    }
}
