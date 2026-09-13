<?php

namespace App\Enums;

enum LocationType: string
{
    case Farm = 'farm';
    case Facility = 'facility';
    case Warehouse = 'warehouse';
    case Store = 'store';
    case Restaurant = 'restaurant';
    case CollectionPoint = 'collection_point';
    case TreatmentCenter = 'treatment_center';
    case ColdRoom = 'cold_room';
    case Other = 'other';
}
