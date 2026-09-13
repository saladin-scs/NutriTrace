<?php

namespace App\Enums;

enum ValorizationType: string
{
    case Composting = 'composting';
    case AnaerobicDigestion = 'anaerobic_digestion';
    case Recycling = 'recycling';
    case Reuse = 'reuse';
    case EnergyRecovery = 'energy_recovery';
    case Landfill = 'landfill';
    case Incineration = 'incineration';
    case Other = 'other';
}
