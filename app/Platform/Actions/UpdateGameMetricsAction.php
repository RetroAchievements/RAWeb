<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Platform\Enums\AchievementType;
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
                ->map(function ($achievement) use ($versionHashFields) {
                    // Exclude missable type from hash calculation, but include progression and win_condition.
                    // progression and win_condition affect beaten stats. missable is a convenience helper.
                    $fieldsForHash = $achievement->only($versionHashFields);
                    if ($fieldsForHash['type'] === AchievementType::Missable) {
                        $fieldsForHash['type'] = null;
                    }

                    return implode('-', $fieldsForHash);
                })
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
        }

        // sync to achievement set
        app()->make(UpsertGameCoreAchievementSetFromLegacyFlagsAction::class)
            ->execute($game);
    }
}
