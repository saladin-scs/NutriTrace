<?php

namespace App\Domain\Certification;

use App\Enums\CertificationStatus;
use Carbon\CarbonInterface;

final class CertificationLifecycle
{
    public function resolveStatus(
        CertificationStatus $current,
        ?CarbonInterface $expiresAt,
        CarbonInterface $now,
    ): CertificationStatus {
        if (in_array($current, [CertificationStatus::Rejected, CertificationStatus::Suspended], true)) {
            return $current;
        }

        if ($expiresAt !== null && $expiresAt->lessThan($now)) {
            return CertificationStatus::Expired;
        }

        return $current;
    }
}
