<?php

namespace App\Console\Commands;

use App\Domain\Analytics\AnalyticsManager;
use Illuminate\Console\Command;

class ScanSupplyChainAnomaliesCommand extends Command
{
    protected $signature = 'nutritrace:scan-anomalies';

    protected $description = 'Scan the supply network for decision-support anomalies (no accusations).';

    public function handle(AnalyticsManager $analytics): int
    {
        $this->info('Scanning distribution, cold chain, stock and mass balance…');
        $created = $analytics->scanAnomalies();
        $this->info(sprintf('%d anomalie(s) créée(s) ou rafraîchie(s).', count($created)));

        foreach (array_slice($created, 0, 10) as $anomaly) {
            $this->line(sprintf(
                '  [%s] %s — %s',
                strtoupper($anomaly->severity->value),
                $anomaly->code,
                $anomaly->title
            ));
        }

        return self::SUCCESS;
    }
}
