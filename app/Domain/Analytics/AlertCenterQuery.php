<?php

namespace App\Domain\Analytics;

use App\Enums\AnomalySeverity;
use App\Enums\AnomalyStatus;
use App\Models\Anomaly;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class AlertCenterQuery
{
    /**
     * @param  array{severity?:string,category?:string,status?:string,q?:string}  $filters
     * @return array<string, mixed>
     */
    public function payload(array $filters = []): array
    {
        $active = Anomaly::query()->active();

        return [
            'kpis' => [
                'open' => (clone $active)->count(),
                'critical' => (clone $active)->where('severity', AnomalySeverity::Critical->value)->count(),
                'warning' => (clone $active)->where('severity', AnomalySeverity::Warning->value)->count(),
                'info' => (clone $active)->where('severity', AnomalySeverity::Info->value)->count(),
                'resolved_today' => Anomaly::query()
                    ->where('status', AnomalyStatus::Resolved->value)
                    ->whereDate('resolved_at', today())
                    ->count(),
            ],
            'feed' => $this->feed($filters, 40),
            'generated_at' => now()->toIso8601String(),
            'disclaimer' => 'Les alertes sont des indicateurs décision-support. Elles appellent une vérification humaine et ne constituent pas une accusation.',
        ];
    }

    /**
     * @param  array{severity?:string,category?:string,status?:string,q?:string}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public function feed(array $filters = [], int $limit = 40): Collection
    {
        $query = Anomaly::query()
            ->with(['coldRoom', 'shipment', 'batch', 'organization', 'vehicle'])
            ->latest('detected_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->active();
        }

        if (! empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($inner) use ($q) {
                $inner->where('title', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('message', 'like', "%{$q}%");
            });
        }

        return $query->limit($limit)->get()->map(fn (Anomaly $a) => $this->serialize($a));
    }

    /**
     * @param  array{severity?:string,category?:string,status?:string,q?:string}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Anomaly::query()
            ->with(['coldRoom', 'shipment', 'batch', 'organization'])
            ->latest('detected_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($inner) use ($q) {
                $inner->where('title', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%")
                    ->orWhere('message', 'like', "%{$q}%");
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(Anomaly $a): array
    {
        return [
            'id' => $a->id,
            'code' => $a->code,
            'category' => $a->category->value,
            'category_label' => $a->category->label(),
            'severity' => $a->severity->value,
            'severity_label' => $a->severity->label(),
            'status' => $a->status->value,
            'status_label' => $a->status->label(),
            'title' => $a->title,
            'message' => $a->message,
            'recommendation' => $a->recommendation,
            'detected_at' => $a->detected_at?->toIso8601String(),
            'metric_value' => $a->metric_value,
            'threshold_value' => $a->threshold_value,
            'cold_room' => $a->coldRoom?->code,
            'shipment' => $a->shipment?->code,
            'batch' => $a->batch?->code,
            'organization' => $a->organization?->name,
            'vehicle' => $a->vehicle?->registration,
            'url' => $a->deepLink(),
            'evidence' => $a->evidence,
        ];
    }
}
