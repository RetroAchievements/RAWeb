<?php

declare(strict_types=1);

namespace App\Platform\Enums;

use App\Community\Enums\RankType;

enum GlobalRankingSortField: string
{
    case Points = 'points';
    case WeightedPoints = 'points_weighted';
    case AchievementsUnlocked = 'achievements_unlocked';
    case AwardsCount = 'awards_count';

    public function rankColumn(): ?string
    {
        return match ($this) {
            self::Points => RankType::Hardcore->rankColumn(),
            self::WeightedPoints => RankType::RetroPoints->rankColumn(),
            default => null,
        };
    }
}
