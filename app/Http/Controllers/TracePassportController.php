<?php

namespace App\Http\Controllers;

use App\Domain\Product\QrPassportUrl;
use App\Domain\Traceability\TraceabilityChainBuilder;
use App\Models\Batch;
use Illuminate\View\View;

class TracePassportController extends Controller
{
    public function __invoke(
        string $code,
        TraceabilityChainBuilder $chain,
        QrPassportUrl $qr,
    ): View {
        $batch = Batch::query()
            ->with([
                'product.category',
                'product.organization',
                'organization',
                'productionLocation',
                'parentBatch.organization',
                'traceabilityEvents.organization',
                'traceabilityEvents.location',
                'certifications',
                'environmentalMetrics',
            ])
            ->where('code', $code)
            ->firstOrFail();

        return view('trace.passport', [
            'batch' => $batch,
            'nodes' => $chain->build($batch),
            'lineage' => $chain->lineage($batch->loadMissing('parentBatch')),
            'passportUrl' => $qr->forCode($batch->code),
            'qrImageUrl' => $qr->qrImageUrl($batch->code, 220),
        ]);
    }
}
