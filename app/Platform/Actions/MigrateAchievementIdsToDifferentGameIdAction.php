<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\CommentableType;
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
        $oldGameIds = Achievement::whereIn('id', $achievementIds)->select(['game_id'])->distinct()->pluck('game_id');

        // Associate the achievements to the new game.
        Achievement::whereIn('id', $achievementIds)->get()->each(function ($achievement) use ($gameId) {
            $achievement->update(['game_id' => $gameId]);
        });

        // Add an audit comment to the new game.
        addArticleComment(
            'Server',
            CommentableType::GameModification,
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
