<?php

namespace App\Domain\Environmental;

/**
 * Score environnemental configurable (indicateur décisionnel, non vérité scientifique).
 *
 * Environmental Score =
 *   30% Origine / circuit court
 * + 20% Transport
 * + 20% Emballage
 * + 15% Certification
 * + 15% Potentiel de valorisation
 */
final class EnvironmentalScoreCalculator
{
    public function __construct(
        private readonly float $originWeight = 0.30,
        private readonly float $transportWeight = 0.20,
        private readonly float $packagingWeight = 0.20,
        private readonly float $certificationWeight = 0.15,
        private readonly float $valorizationWeight = 0.15,
    ) {}

    /**
     * @param  array{origin?: float, transport?: float, packaging?: float, certification?: float, valorization?: float}  $components
     *         Chaque composante est un score 0–100.
     */
    public function calculate(array $components): float
    {
        $score =
            ($components['origin'] ?? 0) * $this->originWeight
            + ($components['transport'] ?? 0) * $this->transportWeight
            + ($components['packaging'] ?? 0) * $this->packagingWeight
            + ($components['certification'] ?? 0) * $this->certificationWeight
            + ($components['valorization'] ?? 0) * $this->valorizationWeight;

        return round(min(100, max(0, $score)), 2);
    }

    /**
     * @return array<string, float>
     */
    public function weights(): array
    {
        return [
            'origin' => $this->originWeight,
            'transport' => $this->transportWeight,
            'packaging' => $this->packagingWeight,
            'certification' => $this->certificationWeight,
            'valorization' => $this->valorizationWeight,
        ];
    }
}
