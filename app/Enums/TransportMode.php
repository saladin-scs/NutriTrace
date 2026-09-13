<?php

namespace App\Enums;

enum TransportMode: string
{
    case Road = 'road';
    case RefrigeratedRoad = 'refrigerated_road';
    case Rail = 'rail';
    case Sea = 'sea';
    case Air = 'air';
    case Other = 'other';
}
