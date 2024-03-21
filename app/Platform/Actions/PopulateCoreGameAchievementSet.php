<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\AchievementSet;
use App\Models\AchievementSetAchievement;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Platform\Enums\GameAchievementSetType;
use App\Platform\Events\CoreGameAchievementSetPopulated;
use Illuminate\Support\Facades\DB;

class PopulateCoreGameAchievementSet
{
    public function execute(Game $game): void
    {
        $game->load(['achievements', 'gameAchievementSets']);

        // If core sets already exist, we'll drop and recreate them.
        $hasCoreSets = $game->gameAchievementSets()->core()->count() > 0;
        if ($hasCoreSets) {
            $this->dropGameExistingCoreSets($game);
        }

        DB::transaction(function () use ($game) {
            $newAchievementSet = $this->createNewAchievementSetFromGame($game);
            $this->createSetAchievements($game, $newAchievementSet);
            $this->createGameAchievementSet($game, $newAchievementSet);

            CoreGameAchievementSetPopulated::dispatch($game);
        });
    }

    private function createGameAchievementSet(Game $game, AchievementSet $achievementSet): GameAchievementSet
    {
        /** @var GameAchievementSet $gameAchievementSet */
        $gameAchievementSet = GameAchievementSet::create([
            'game_id' => $game->id,
            'achievement_set_id' => $achievementSet->id,
            'type' => GameAchievementSetType::Core,
            'title' => null,
            'order_column' => 1,
        ]);

        return $gameAchievementSet;
    }

    private function createNewAchievementSetFromGame(Game $game): AchievementSet
    {
        list($officialAchievements, $unofficialAchievements) = $game->achievements->partition(function ($achievement) {
            return $achievement->isPublished;
        });

        /** @var AchievementSet $achievementSet */
        $achievementSet = AchievementSet::create([
            'players_total' => $game->players_total,
            'players_hardcore' => $game->players_hardcore,
            'achievements_published' => $officialAchievements->count(),
            'achievements_unpublished' => $unofficialAchievements->count(),
            'points_total' => $officialAchievements->sum('points'),
            'points_weighted' => $officialAchievements->sum('TrueRatio'),
        ]);

        return $achievementSet;
    }

    private function createSetAchievements(Game $game, AchievementSet $achievementSet): void
    {
        // Create set achievements for each achievement associated with the game.
        // Associate those set achievements with the incoming `AchievementSet` entity.
        foreach ($game->achievements as $achievement) {
            $setAchievement = new AchievementSetAchievement([
                'achievement_set_id' => $achievementSet->id,
                'achievement_id' => $achievement->id,
                'order_column' => $achievement->DisplayOrder,
            ]);

            $setAchievement->save();
        }
    }

    private function dropGameExistingCoreSets(Game $game): void
    {
        DB::transaction(function () use ($game) {
            $gameAchievementSets = GameAchievementSet::where('game_id', $game->id)
                ->where('type', GameAchievementSetType::Core)
                ->get();

            foreach ($gameAchievementSets as $gameAchievementSet) {
                $achievementSet = $gameAchievementSet->achievementSet;
                if ($achievementSet) {
                    AchievementSetAchievement::where('achievement_set_id', $achievementSet->id)->delete();

                    // Once AchievementSetAchievement rows are deleted, delete the AchievementSet.
                    $achievementSet->delete();
                }

                // Finally, delete the GameAchievementSet itself.
                $gameAchievementSet->delete();
            }
        });
    }
}
