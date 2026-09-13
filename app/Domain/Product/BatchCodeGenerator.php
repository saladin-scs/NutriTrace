<?php

namespace App\Domain\Product;

final class BatchCodeGenerator
{
    public function generate(int $sequence, ?int $year = null): string
    {
        $year ??= (int) date('Y');

        return sprintf('NT-%d-%06d', $year, $sequence);
    }
}
