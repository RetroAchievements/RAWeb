<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\ArticleType;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Jobs\UpdatePlayerGameMetricsJob;
use Illuminate\Support\Str;

class MigrateAchievementIdsToDifferentGameIdAction
{
    public function execute(array $achievementIds, int $gameId, User $user): void
    {
        // Determine which game(s) the achievements are coming from.
        $oldGameIds = Achievement::whereIn('ID', $achievementIds)->select(['GameID'])->distinct()->pluck('GameID');

        // Associate the achievements to the new game.
        Achievement::whereIn('ID', $achievementIds)->get()->each(function ($achievement) use ($gameId) {
            $achievement->update(['GameID' => $gameId]);
        });

        // Add an audit comment to the new game.
        addArticleComment(
            'Server',
            ArticleType::GameModification,
            $gameId,
            "{$user->display_name} migrated " . Str::plural('achievement', count($achievementIds)) . ' ' .
                implode(',', $achievementIds) . ' from ' .
                Str::plural('game', count($oldGameIds)) . ' ' . $oldGameIds->implode(',') . '.'
        );

        // Update all affected player game metrics.
        $affectedUserIds = PlayerAchievement::whereIn('achievement_id', $achievementIds)
            ->select(['user_id'])
            ->distinct()
            ->pluck('user_id');
        foreach ($affectedUserIds as $userId) {
            // Ensure player_game entries exist for the new game for all affected users.
            if (!PlayerGame::where('game_id', $gameId)->where('user_id', $userId)->exists()) {
                $playerGame = new PlayerGame(['user_id' => $userId, 'game_id' => $gameId]);
                $playerGame->save();
            }

            dispatch(new UpdatePlayerGameMetricsJob($userId, $gameId));
            foreach ($oldGameIds as $oldGameId) {
                dispatch(new UpdatePlayerGameMetricsJob($userId, $oldGameId));
            }
        }

        // Update achievement_sets associated with the given achievement IDs.
        $upsertAction = new UpsertGameCoreAchievementSetFromLegacyFlagsAction();
        $upsertAction->execute(Game::find($gameId));
        foreach ($oldGameIds as $oldGameId) {
            $upsertAction->execute(Game::find($oldGameId));
        }
    }
}
