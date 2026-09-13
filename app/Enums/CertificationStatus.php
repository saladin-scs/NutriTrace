<?php

namespace App\Enums;

enum CertificationStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Expired = 'expired';
    case Rejected = 'rejected';
    case Suspended = 'suspended';
}
