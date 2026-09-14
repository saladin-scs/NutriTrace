<?php

namespace App\Events;

use App\Models\Anomaly;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnomalyDetected
{
    use Dispatchable, SerializesModels;

    public function __construct(public Anomaly $anomaly) {}
}
