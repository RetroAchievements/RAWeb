<?php

declare(strict_types=1);

namespace App\Api\V2\AchievementSetClaims\Filters;

use App\Community\Enums\ClaimSetType;

final class ClaimSetTypeFilter extends AbstractClaimEnumFilter
{
    public function key(): string
    {
        return 'setType';
    }

    protected function column(): string
    {
        return 'set_type';
    }

    protected function enumClass(): string
    {
        return ClaimSetType::class;
    }

    protected function errorLabel(): string
    {
        return 'set type';
    }
}
