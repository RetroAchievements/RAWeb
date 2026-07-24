<?php

declare(strict_types=1);

namespace App\Community\Enums;

use App\Platform\Enums\GlobalRankingMode;

enum RankType: string
{
    case Hardcore = 'hardcore';
    case Casual = 'casual';
    case RetroPoints = 'retro_points';

    /**
     * Which partition of the materialized rankings this rank type is drawn from.
     */
    public function mode(): GlobalRankingMode
    {
        return match ($this) {
            self::Casual => GlobalRankingMode::Casual,
            self::Hardcore, self::RetroPoints => GlobalRankingMode::Hardcore,
        };
    }

    /**
     * The materialized rankings column holding this rank type's precomputed rank.
     */
    public function rankColumn(): string
    {
        return $this === self::RetroPoints ? 'weighted_rank_number' : 'rank_number';
    }
}
