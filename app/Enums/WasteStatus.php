<?php

namespace App\Enums;

enum WasteStatus: string
{
    case Generated = 'generated';
    case Sorted = 'sorted';
    case CollectionRequested = 'collection_requested';
    case Collected = 'collected';
    case InTransit = 'in_transit';
    case Treated = 'treated';
    case Valorized = 'valorized';
    case Disposed = 'disposed';
}
