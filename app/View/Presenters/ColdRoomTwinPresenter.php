<?php

namespace App\View\Presenters;

/**
 * Presentation factorisation for Cold Room digital twin KPIs.
 */
final class ColdRoomTwinPresenter
{
    /**
     * @param  array<string, mixed>  $twin
     */
    public function __construct(private array $twin) {}

    public function code(): string
    {
        return (string) ($this->twin['room']['code'] ?? '');
    }

    public function occupancyLabel(): string
    {
        $pct = (float) ($this->twin['kpis']['occupancy_pct'] ?? 0);

        return match (true) {
            $pct >= 95 => 'CRITIQUE',
            $pct >= 85 => 'ÉLEVÉE',
            $pct >= 60 => 'NORMALE',
            default => 'FAIBLE',
        };
    }

    public function occupancyToneClass(): string
    {
        $pct = (float) ($this->twin['kpis']['occupancy_pct'] ?? 0);

        return match (true) {
            $pct >= 95 => 'text-rose-700',
            $pct >= 85 => 'text-amber-700',
            default => 'text-emerald-800',
        };
    }

    public function temperatureToneClass(): string
    {
        return match ($this->twin['kpis']['temperature_status'] ?? 'normal') {
            'critical' => 'text-rose-700',
            'warning' => 'text-amber-700',
            default => 'text-emerald-800',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function kpis(): array
    {
        return $this->twin['kpis'] ?? [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function alerts(): array
    {
        return $this->twin['alerts'] ?? [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function openStock(): array
    {
        return ($this->twin['open_stock'] ?? []) instanceof \Illuminate\Support\Collection
            ? $this->twin['open_stock']->all()
            : (array) ($this->twin['open_stock'] ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    public function room(): array
    {
        return $this->twin['room'] ?? [];
    }

    public function disclaimer(): string
    {
        return (string) ($this->twin['disclaimer'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    public function raw(): array
    {
        return $this->twin;
    }
}
