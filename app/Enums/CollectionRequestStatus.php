<?php

namespace App\Enums;

enum CollectionRequestStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
