<?php

namespace App\Domain\Collection;

use App\Enums\CollectionRequestStatus;

final class CollectionWorkflow
{
    /**
     * @return list<CollectionRequestStatus>
     */
    public function allowedTransitions(CollectionRequestStatus $from): array
    {
        return match ($from) {
            CollectionRequestStatus::Pending => [
                CollectionRequestStatus::Accepted,
                CollectionRequestStatus::Cancelled,
            ],
            CollectionRequestStatus::Accepted => [
                CollectionRequestStatus::Scheduled,
                CollectionRequestStatus::Cancelled,
            ],
            CollectionRequestStatus::Scheduled => [
                CollectionRequestStatus::Completed,
                CollectionRequestStatus::Cancelled,
            ],
            CollectionRequestStatus::Completed,
            CollectionRequestStatus::Cancelled => [],
        };
    }

    public function canTransition(CollectionRequestStatus $from, CollectionRequestStatus $to): bool
    {
        return in_array($to, $this->allowedTransitions($from), true);
    }
}
