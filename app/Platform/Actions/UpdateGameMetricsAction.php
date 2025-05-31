<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Platform\Events\GameMetricsUpdated;
use App\Platform\Jobs\UpdateGamePlayerGamesJob;
use Illuminate\Support\Facades\Log;

// Game metrics managed by this action are related to published achievements and their definitions
// For player-related metrics, see UpdateGamePlayerCountAction and UpdateGameBeatenMetricsAction.
class UpdateGameMetricsAction
{
    public function execute(Game $game): void
    {
        $game->achievements_published = $game->achievements()->published()->count();
        $game->achievements_unpublished = $game->achievements()->unpublished()->count();

        $game->points_total = $game->achievements()->published()->sum('points');
        // NOTE $game->TotalTruePoints are updated separately

        $achievementSetVersionChanged = false;
        if ($game->achievements_published || $game->achievements_unpublished) {
            $versionHashFields = ['ID', 'MemAddr', 'type', 'Points'];
            $achievementSetVersionHashPayload = $game->achievements()->published()
                ->orderBy('ID')
                ->get($versionHashFields)
                ->map(fn ($achievement) => implode('-', $achievement->only($versionHashFields)))
                ->implode('-');
            $game->achievement_set_version_hash = hash('sha256', $achievementSetVersionHashPayload);

            $achievementSetVersionChanged = $game->isDirty('achievement_set_version_hash');
        }

        $game->saveQuietly();

        GameMetricsUpdated::dispatch($game);

        if ($achievementSetVersionChanged) {
            Log::info("Hash change detected for game [" . $game->id . "]. Queueing all outdated player games.");
            dispatch(new UpdateGamePlayerGamesJob($game->id))
                ->onQueue('game-player-games');

            // one or more achievements was added/removed/modified. sync to achievement set
            app()->make(UpsertGameCoreAchievementSetFromLegacyFlagsAction::class)
                ->execute($game);
        }
    }
}
