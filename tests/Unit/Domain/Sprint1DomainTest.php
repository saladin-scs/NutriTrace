<?php

namespace Tests\Unit\Domain;

use App\Domain\Collection\CollectionWorkflow;
use App\Domain\Environmental\EnvironmentalScoreCalculator;
use App\Domain\Product\BatchCodeGenerator;
use App\Domain\Waste\ValorizationRate;
use App\Enums\CollectionRequestStatus;
use PHPUnit\Framework\TestCase;

class Sprint1DomainTest extends TestCase
{
    public function test_batch_code_format(): void
    {
        $code = (new BatchCodeGenerator)->generate(184, 2026);

        $this->assertSame('NT-2026-000184', $code);
    }

    public function test_environmental_score_weights(): void
    {
        $calculator = new EnvironmentalScoreCalculator;
        $score = $calculator->calculate([
            'origin' => 100,
            'transport' => 100,
            'packaging' => 100,
            'certification' => 100,
            'valorization' => 100,
        ]);

        $this->assertSame(100.0, $score);
    }

    public function test_valorization_rate(): void
    {
        $rate = (new ValorizationRate)->calculate(200, 150);

        $this->assertSame(75.0, $rate);
    }

    public function test_collection_workflow_transitions(): void
    {
        $workflow = new CollectionWorkflow;

        $this->assertTrue($workflow->canTransition(
            CollectionRequestStatus::Pending,
            CollectionRequestStatus::Accepted
        ));
        $this->assertFalse($workflow->canTransition(
            CollectionRequestStatus::Pending,
            CollectionRequestStatus::Completed
        ));
    }
}
