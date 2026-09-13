<?php

namespace App\Domain\Waste;

final class ValorizationRate
{
    public function calculate(float $generated, float $valorized): float
    {
        if ($generated <= 0) {
            return 0.0;
        }

        return round(min(100, max(0, ($valorized / $generated) * 100)), 2);
    }
}
