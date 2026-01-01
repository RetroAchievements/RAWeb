<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Data\TrendingGameData;
use App\Models\Game;
use App\Platform\Data\GameData;
use Illuminate\Support\Collection;

/**
 * TODO bake more intelligence into this algorithm
 *
 * come up with some kind of "trending score" that would combine various facets, such as:
 * - boost for recent player sessions
 * - boost for recent achievement unlocks
 * - boost if game is gaining new unique players
 * - boost if people are getting beats/masteries
 * - decay the score every N days the game has been trending. after it isn't trending anymore, stop the decay.
 */

// TODO allow filtering by system

class BuildTrendingGamesAction
{
    /**
     * @return Collection<int, TrendingGameData>
     */
    public function execute(): Collection
    {
        $players = (new LoadThinActivePlayersListAction())->execute();

        // Group players by game_id and count them.
        $gameCounts = [];
        foreach ($players as $player) {
            $gameCounts[$player['game_id']] = ($gameCounts[$player['game_id']] ?? 0) + 1;
        }

        // Sort by count in descending order.
        arsort($gameCounts);

        // Take the top 12 game IDs as candidates.
        $candidateGameIds = array_slice(array_keys($gameCounts), 0, 12);

        // Find games that belong to hubs with mature content.
        $matureGameIds = Game::whereIn('id', $candidateGameIds)
            ->whereHas('hubs', function ($query) {
                $query->where('has_mature_content', true);
            })
            ->pluck('id')
            ->toArray();

        // Filter out mature content games and take the top 4.
        $trendingGameIds = array_slice(
            array_filter($candidateGameIds, fn ($id) => !in_array($id, $matureGameIds)),
            0,
            4
        );

        // Convert to TrendingGameData objects.
        return Game::with('system')->whereIn('id', $trendingGameIds)
            ->get()
            ->map(function (Game $game) use ($gameCounts) {
                $gameData = GameData::from($game)->include(
                    'badgeUrl',
                    'system.iconUrl',
                    'system.nameShort'
                );

                return new TrendingGameData(
                    game: $gameData,
                    playerCount: $gameCounts[$game->id] ?? 0
                );
            })
            ->sortByDesc(fn (TrendingGameData $data) => $data->playerCount)
            ->values();
    }
}
