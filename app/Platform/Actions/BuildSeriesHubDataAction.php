<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameSet;
use App\Platform\Data\GameData;
use App\Platform\Data\GameSetData;
use App\Platform\Data\SeriesHubData;
use Illuminate\Support\Collection;
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
        $query = $seriesHub->games()
            ->where('GameData.Title', 'not like', '%[Subset -%');

        // Calculate aggregated statistics.
        $stats = $query->select([
            DB::raw('COUNT(DISTINCT GameData.ID) as total_game_count'),
            DB::raw('SUM(GameData.achievements_published) as total_achievements'),
            DB::raw('SUM(GameData.points_total) as total_points'),
        ])->first();

        // Get all games sorted by release date.
        $allGames = $seriesHub->games()
            ->where('GameData.Title', 'not like', '%[Subset -%')
            ->orderBy('GameData.released_at', 'asc')
            ->orderBy('GameData.Title', 'asc') // Secondary sort for games with same release date.
            ->get();

        // Filter to only games with achievements for display (but keep all for counting).
        $gamesWithAchievements = $allGames->filter(fn ($g) => $g->achievements_published > 0)->values();

        // Find the current game's position in the list of games with achievements.
        $currentGameIndex = $gamesWithAchievements->search(fn ($g) => $g->id === $game->id);

        // Determine which games to show (up to 5, centered around current game if possible).
        $gamesToShow = $this->getGamesToShow($gamesWithAchievements, $currentGameIndex);

        $totalGameCount = (int) $stats->total_game_count;
        // Additional games count should show all remaining games in the series.
        $additionalGameCount = max(0, $totalGameCount - count($gamesToShow));

        return new SeriesHubData(
            hub: GameSetData::from($seriesHub)->include('badgeUrl'),
            totalGameCount: $totalGameCount,
            achievementsPublished: (int) ($stats->total_achievements ?? 0),
            pointsTotal: (int) ($stats->total_points ?? 0),
            topGames: array_map(
                fn ($game) => GameData::fromGame($game)->include('badgeUrl'),
                $gamesToShow
            ),
            additionalGameCount: $additionalGameCount,
        );
    }

    private function findSeriesHub(Game $game): ?GameSet
    {
        // Get all hubs for this game.
        $hubs = $game->hubs()->get();

        if ($hubs->isEmpty()) {
            return null;
        }

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

    /**
     * Get up to 5 games to show, centered around the current game if possible.
     *
     * @param Collection<int, Game> $allGames
     * @param int|false $currentGameIndex
     */
    private function getGamesToShow($allGames, $currentGameIndex): array
    {
        $maxGamesToShow = 5;
        $totalGames = $allGames->count();

        // If we have 5 or fewer games total, show them all.
        if ($totalGames <= $maxGamesToShow) {
            return $allGames->all();
        }

        // Safety: if the current game wasn't found (shouldn't happen), just show the first 5.
        if ($currentGameIndex === false) {
            return $allGames->take($maxGamesToShow)->all();
        }

        // Try to center the current game in the list of 5.
        // Ideal position is index 2 (middle of 0,1,2,3,4).
        $idealStartIndex = $currentGameIndex - 2;

        // Adjust if we're too close to the start.
        if ($idealStartIndex < 0) {
            $idealStartIndex = 0;
        }

        // Adjust if we're too close to the end.
        if ($idealStartIndex + $maxGamesToShow > $totalGames) {
            $idealStartIndex = $totalGames - $maxGamesToShow;
        }

        return $allGames->slice($idealStartIndex, $maxGamesToShow)->values()->all();
    }
}
