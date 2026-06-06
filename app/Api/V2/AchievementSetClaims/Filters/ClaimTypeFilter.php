<?php

declare(strict_types=1);

namespace App\Api\V2\AchievementSetClaims\Filters;

use App\Community\Enums\ClaimType;

final class ClaimTypeFilter extends AbstractClaimEnumFilter
{
    public function key(): string
    {
        return 'claimType';
    }

    protected function column(): string
    {
        return 'claim_type';
    }

    protected function enumClass(): string
    {
        return ClaimType::class;
    }

    protected function errorLabel(): string
    {
        return 'claim type';
    }
}
