<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\ArticleType;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Jobs\UpdateGameMetricsJob;
use App\Platform\Jobs\UpdatePlayerGameMetricsJob;
use Illuminate\Support\Str;

class MigrateAchievementIdsToDifferentGameId
{
    public function execute(array $achievementIds, int $gameId, User $user): void
    {
        // Determine which game(s) the achievements are coming from.
        $oldGameIds = Achievement::whereIn('ID', $achievementIds)->select(['GameID'])->distinct()->pluck('GameID');

        // Associate the achievements to the new game.
        Achievement::whereIn('ID', $achievementIds)->update(['GameID' => $gameId]);

        // Add an audit comment to the new game.
        addArticleComment(
            'Server',
            ArticleType::GameModification,
            $gameId,
            "$user migrated " . Str::plural('achievement', count($achievementIds)) . ' ' .
                implode(',', $achievementIds) . ' from ' .
                Str::plural('game', count($oldGameIds)) . ' ' . $oldGameIds->implode(',') . '.'
        );

        // Ensure player_game entries exist for the new game for all affected users.
        foreach (PlayerAchievement::whereIn('achievement_id', $achievementIds)->select(['user_id'])->distinct()->pluck('user_id') as $userId) {
            if (!PlayerGame::where('game_id', $gameId)->where('user_id', $userId)->exists()) {
                $playerGame = new PlayerGame(['user_id' => $userId, 'game_id' => $gameId]);
                $playerGame->save();
                dispatch(new UpdatePlayerGameMetricsJob($userId, $gameId));
            }
        }

        // Update the metrics on the new game and the old game(s).
        dispatch(new UpdateGameMetricsJob($gameId))->onQueue('game-metrics');
        foreach ($oldGameIds as $oldGameId) {
            dispatch(new UpdateGameMetricsJob($oldGameId))->onQueue('game-metrics');
        }

        // Update achievement_sets associated with the given achievement IDs.
        $upsertAction = new UpsertGameCoreAchievementSetFromLegacyFlags();
        $upsertAction->execute(Game::find($gameId));
        foreach ($oldGameIds as $oldGameId) {
            $upsertAction->execute(Game::find($oldGameId));
        }
    }
}
