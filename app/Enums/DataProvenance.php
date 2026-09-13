<?php

namespace App\Enums;

enum DataProvenance: string
{
    case Declared = 'declared';
    case Verified = 'verified';
    case Calculated = 'calculated';
    case Estimated = 'estimated';
}
