<?php

namespace App\Domain\Analytics;

/**
 * Documented, configurable Supply Chain Health Score.
 *
 * Default weights (sum = 100):
 *  - Delivery performance .... 30
 *  - Cold chain compliance ... 25
 *  - Traceability coverage ... 20
 *  - Waste performance ....... 15
 *  - Environmental ........... 10
 *
 * Each pillar is scored 0–100 then combined. Methodology is informational.
 */
final class SupplyChainHealthScore
{
    /** @var array<string, int> */
    public const DEFAULT_WEIGHTS = [
        'delivery' => 30,
        'cold_chain' => 25,
        'traceability' => 20,
        'waste' => 15,
        'environmental' => 10,
    ];

    /**
     * @param  array<string, float|int>  $pillars  keys match DEFAULT_WEIGHTS, values 0–100
     * @param  array<string, int>|null  $weights
     * @return array<string, mixed>
     */
    public function compute(array $pillars, ?array $weights = null): array
    {
        $weights = $weights ?? self::DEFAULT_WEIGHTS;
        $totalWeight = array_sum($weights) ?: 100;
        $score = 0.0;
        $breakdown = [];

        foreach ($weights as $key => $weight) {
            $pillar = max(0.0, min(100.0, (float) ($pillars[$key] ?? 0)));
            $contribution = ($pillar * $weight) / $totalWeight;
            $score += $contribution;
            $breakdown[$key] = [
                'score' => round($pillar, 1),
                'weight' => $weight,
                'contribution' => round($contribution, 2),
            ];
        }

        $rounded = (int) round($score);

        return [
            'score' => $rounded,
            'label' => $this->label($rounded),
            'pillars' => $breakdown,
            'methodology' => [
                'description' => 'Score composite pondéré 0–100 pour aide à la décision opérationnelle.',
                'weights' => $weights,
                'disclaimer' => 'Indicateur décision-support. Ne constitue ni notation réglementaire ni accusation d’acteur.',
            ],
        ];
    }

    public function label(int $score): string
    {
        return match (true) {
            $score >= 90 => 'Excellent',
            $score >= 75 => 'Bon',
            $score >= 60 => 'Acceptable',
            $score >= 40 => 'Fragile',
            default => 'Critique',
        };
    }
}
