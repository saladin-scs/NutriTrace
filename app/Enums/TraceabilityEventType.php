<?php

namespace App\Enums;

enum TraceabilityEventType: string
{
    case Production = 'production';
    case Receipt = 'receipt';
    case Transformation = 'transformation';
    case Distribution = 'distribution';
    case Sale = 'sale';
    case Consumption = 'consumption';
    case Loss = 'loss';
    case WasteDeclared = 'waste_declared';
    case ColdStorageEntry = 'cold_storage_entry';
    case ColdStorageExit = 'cold_storage_exit';
}
