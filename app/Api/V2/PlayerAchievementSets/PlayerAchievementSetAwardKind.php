<?php

declare(strict_types=1);

namespace App\Api\V2\PlayerAchievementSets;

use App\Models\PlayerAchievementSet;
use Illuminate\Database\Eloquent\Builder;

enum PlayerAchievementSetAwardKind: string
{
    case Completed = 'completed';
    case Mastered = 'mastered';

    /**
     * @param Builder<PlayerAchievementSet> $query
     * @return Builder<PlayerAchievementSet>
     */
    public function apply(Builder $query): Builder
    {
        return match ($this) {
            self::Completed => $query->whereNotNull('completed_at'),
            self::Mastered => $query->whereNotNull('completed_hardcore_at'),
        };
    }
}
