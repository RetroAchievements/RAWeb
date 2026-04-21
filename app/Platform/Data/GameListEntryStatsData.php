<?php

declare(strict_types=1);

namespace App\Platform\Data;

use App\Models\Game;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript('GameListEntryStats')]
class GameListEntryStatsData extends Data
{
    public function __construct(
        public int $coreSetMedianTimeToCompleteHardcore,
        public int $coreSetPlayersHardcore,
        public int $coreSetTimesCompletedHardcore,
    ) {
    }

    public static function fromGame(Game $game): self
    {
        return new self(
            coreSetMedianTimeToCompleteHardcore: $game->core_set_median_time_to_complete_hardcore ?? 0,
            coreSetPlayersHardcore: $game->core_set_players_hardcore ?? 0,
            coreSetTimesCompletedHardcore: $game->core_set_times_completed_hardcore ?? 0,
        );
    }
}
