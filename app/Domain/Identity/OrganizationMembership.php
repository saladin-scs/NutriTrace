<?php

namespace App\Domain\Identity;

final class OrganizationMembership
{
    public function __construct(
        public readonly int $userId,
        public readonly int $organizationId,
        public readonly string $roleSlug,
        public readonly bool $isPrimary = false,
    ) {}
}
