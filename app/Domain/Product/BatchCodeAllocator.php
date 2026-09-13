<?php

namespace App\Domain\Product;

use App\Models\Batch;

final class BatchCodeAllocator
{
    public function __construct(private BatchCodeGenerator $generator) {}

    public function allocate(?int $year = null): string
    {
        $year ??= (int) date('Y');
        $prefix = sprintf('NT-%d-', $year);

        $last = Batch::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('code')
            ->value('code');

        $sequence = $last ? ((int) substr((string) $last, -6)) + 1 : 1;

        return $this->generator->generate($sequence, $year);
    }
}
