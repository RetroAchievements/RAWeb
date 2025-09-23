<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameSet;
use App\Models\GameSetGame;
use App\Platform\Data\GameSetData;
use App\Platform\Data\SeriesHubData;
use Illuminate\Support\Facades\DB;

class BuildSeriesHubDataAction
{
    public function execute(Game $game): ?SeriesHubData
    {
        // Find the game's series or subseries hub.
        $seriesHub = $this->findSeriesHub($game);

        if (!$seriesHub) {
            return null;
        }

        // Get all games in the hub (excluding subsets).
        $query = $seriesHub->games()->where('GameData.Title', 'not like', '%[Subset -%');

        $allGames = $query->get();

        // Calculate aggregated statistics.
        $stats = $query->select([
            DB::raw('COUNT(DISTINCT GameData.ID) as total_game_count'),
            DB::raw('SUM(GameData.achievements_published) as total_achievements'),
            DB::raw('SUM(GameData.points_total) as total_points'),
        ])->first();

        $gamesWithAchievements = $allGames->filter(fn ($g) => $g->achievements_published > 0)->values();

        return new SeriesHubData(
            hub: GameSetData::from($seriesHub)->include('badgeUrl'),
            gamesWithAchievementsCount: $gamesWithAchievements->count(),
            totalGameCount: (int) $stats->total_game_count,
            achievementsPublished: (int) ($stats->total_achievements ?? 0),
            pointsTotal: (int) ($stats->total_points ?? 0)
        );
    }

    private function findSeriesHub(Game $game): ?GameSet
    {
        // Use already loaded hubs instead of querying again.
        // The hubs are already loaded in LoadGameWithRelationsAction.
        $hubs = $game->hubs;

        if ($hubs->isEmpty()) {
            return null;
        }

        // Load games count only for these hubs.
        $hubIds = $hubs->pluck('id');
        $gameCounts = GameSetGame::query()
            ->selectRaw('game_set_id, COUNT(DISTINCT game_id) as count')
            ->whereIn('game_set_id', $hubIds)
            ->groupBy('game_set_id')
            ->pluck('count', 'game_set_id');

        // Attach counts and sort by largest hub.
        $hubs->each(fn ($hub) => $hub->setAttribute('games_count', $gameCounts[$hub->id] ?? 0));
        $hubs = $hubs->sortByDesc('games_count');

        // First, check for a subseries hub (these are more specific).
        $subseriesHub = $hubs->first(fn ($hub) => str_contains($hub->title, 'Subseries -'));
        if ($subseriesHub) {
            return $subseriesHub;
        }

        // Then, check for a series hub.
        $seriesHub = $hubs->first(fn ($hub) => str_contains($hub->title, 'Series -'));
        if ($seriesHub) {
            return $seriesHub;
        }

        return null;
    }
}
