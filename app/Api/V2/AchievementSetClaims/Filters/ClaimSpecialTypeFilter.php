<?php

declare(strict_types=1);

namespace App\Api\V2\AchievementSetClaims\Filters;

use App\Community\Enums\ClaimSpecial;

final class ClaimSpecialTypeFilter extends AbstractClaimEnumFilter
{
    public function key(): string
    {
        return 'specialType';
    }

    protected function column(): string
    {
        return 'special_type';
    }

    protected function enumClass(): string
    {
        return ClaimSpecial::class;
    }

    protected function errorLabel(): string
    {
        return 'special type';
    }
}
