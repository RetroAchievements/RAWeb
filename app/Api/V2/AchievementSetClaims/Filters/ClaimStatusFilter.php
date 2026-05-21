<?php

declare(strict_types=1);

namespace App\Api\V2\AchievementSetClaims\Filters;

use App\Community\Enums\ClaimStatus;

final class ClaimStatusFilter extends AbstractClaimEnumFilter
{
    public function key(): string
    {
        return 'status';
    }

    protected function column(): string
    {
        return 'status';
    }

    protected function enumClass(): string
    {
        return ClaimStatus::class;
    }

    protected function errorLabel(): string
    {
        return 'claim status';
    }
}
