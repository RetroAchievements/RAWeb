<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\GameAchievementSet;
use App\Models\PlayerAchievementSet;
use App\Models\PlayerGame;
use App\Platform\Enums\AchievementSetType;

class GetAwardTimeTakenAction
{
    public function execute(int $userId, array $gameIds, string $awardKind): array
    {
        $result = [];

        if ($awardKind === 'mastered' || $awardKind === 'completed') {
            $coreAchievementSetIds = GameAchievementSet::query()
                ->whereIn('game_id', $gameIds)
                ->where('type', AchievementSetType::Core)
                ->pluck('game_id', 'achievement_set_id')
                ->toArray();
            if (!empty($coreAchievementSetIds)) {
                $playerAchievementSets = PlayerAchievementSet::query()
                    ->whereIn('achievement_set_id', array_keys($coreAchievementSetIds))
                    ->where('user_id', $userId)
                    ->select(['achievement_set_id', 'time_taken', 'time_taken_hardcore'])
                    ->get();
                foreach ($playerAchievementSets as $playerAchievementSet) {
                    $gameId = $coreAchievementSetIds[$playerAchievementSet->achievement_set_id];
                    $result[$gameId] = ($awardKind === 'mastered') ?
                        $playerAchievementSet->time_taken_hardcore :
                        $playerAchievementSet->time_taken;
                }
            }
        } elseif ($awardKind !== null) {
            $playerGames = PlayerGame::query()
                ->whereIn('game_id', $gameIds)
                ->where('user_id', $userId)
                ->select(['game_id', 'time_to_beat', 'time_to_beat_hardcore'])
                ->get();
            foreach ($playerGames as $playerGame) {
                $result[$playerGame->game_id] = ($awardKind === 'beaten-hardcore') ?
                    $playerGame->time_to_beat_hardcore : $playerGame->time_to_beat;
            }
        }

        return $result;
    }
}
