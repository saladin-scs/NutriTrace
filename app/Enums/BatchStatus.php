<?php

namespace App\Enums;

enum BatchStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Transformed = 'transformed';
    case Distributed = 'distributed';
    case Sold = 'sold';
    case Expired = 'expired';
    case Recalled = 'recalled';
    case Closed = 'closed';
}
