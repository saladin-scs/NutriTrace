<?php

namespace App\Domain\Distribution;

use App\Models\Route as LogisticsRoute;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

final class RouteCodeAllocator
{
    public function allocate(?int $year = null): string
    {
        $year ??= (int) date('Y');
        $prefix = sprintf('RT-%d-', $year);

        return DB::transaction(function () use ($prefix, $year) {
            $last = LogisticsRoute::query()
                ->where('code', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('code')
                ->value('code');

            $sequence = 1;
            if (is_string($last) && preg_match('/RT-'.$year.'-(\d+)$/', $last, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            }

            return sprintf('RT-%d-%06d', $year, $sequence);
        });
    }
}
