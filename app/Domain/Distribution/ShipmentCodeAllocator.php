<?php

namespace App\Domain\Distribution;

use App\Models\Shipment;
use Illuminate\Support\Facades\DB;

final class ShipmentCodeAllocator
{
    public function allocate(?int $year = null): string
    {
        $year ??= (int) date('Y');
        $prefix = sprintf('SH-%d-', $year);

        return DB::transaction(function () use ($prefix, $year) {
            $last = Shipment::query()
                ->withTrashed()
                ->where('code', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('code')
                ->value('code');

            $sequence = 1;
            if (is_string($last) && preg_match('/SH-'.$year.'-(\d+)$/', $last, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            }

            return sprintf('SH-%d-%06d', $year, $sequence);
        });
    }
}
