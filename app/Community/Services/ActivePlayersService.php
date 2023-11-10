<?php

declare(strict_types=1);

namespace App\Community\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class ActivePlayersService
{
    public function loadActivePlayers(?string $searchValue = null, bool $fetchAll = false): array
    {
        $allActivePlayers = Cache::remember(
            'currently-active:20230921',
            Carbon::now()->addMinutes(2),
            function () {
                return collect(getLatestRichPresenceUpdates())
                    ->keyBy('User')
                    ->map(function ($user) {
                        $user['InGame'] = true;

                        return $user;
                    });
            }
        );

        $filteredActivePlayers = $allActivePlayers;
        $filteredActivePlayers = $this->useDevelopmentGameTitle($filteredActivePlayers);

        $trendingGames = $this->computeTrendingGames($filteredActivePlayers);

        if ($searchValue) {
            // Split the search string by the '|' operator to support "OR" searches.
            $searchTerms = array_filter(explode('|', $searchValue), function ($term) {
                return trim($term) !== '';
            });

            $filteredActivePlayers = $filteredActivePlayers->filter(function ($player) use ($searchTerms) {
                // Check if any of the search terms are present in any of the player's attributes.
                foreach ($searchTerms as $term) {
                    if (stripos($player['User'], $term) !== false
                        || stripos($player['GameTitle'], $term) !== false
                        || stripos($player['ConsoleName'], $term) !== false
                        || stripos($player['RichPresenceMsg'], $term) !== false) {
                        return true;
                    }
                }

                return false;
            });
        }

        $records = $filteredActivePlayers->values();

        // If we have no search or filter, take the top 20 players on the list.
        if (!$searchValue && !$fetchAll) {
            $records = $records->take(20);
        }

        $records = $records->toArray();

        return [
            'total' => $allActivePlayers->count(),
            'count' => count($records),
            'records' => $records,
            'trendingGames' => $trendingGames,
        ];
    }

    private function computeTrendingGames(mixed $activePlayers): array
    {
        $mostPopularNewMemberGameIds = [
            228,    // Super Mario World
            1446,   // Super Mario Bros.
            10003,  // Super Mario 64
            1,      // Sonic the Hedgehog
            337,    // Donkey Kong Country
            11240,  // Castlevania: Symphony of the Night
            319,    // Chrono Trigger
            637,    // Mega Man X
            355,    // Legend of Zelda, The: A Link to the Past
            668,    // Pokemon Emerald Version
            1995,   // Super Mario Bros. 3
            236,    // Super Metroid
            379,    // Aladdin
            11244,  // Metal Gear Solid
            10434,  // Crash Bandicoot
            466,    // Donkey Kong Country 2: Diddy's Kong Quest
            1447,   // Contra | Probotector
            515,    // Pokemon FireRed Version
            559,    // Legend of Zelda, The: The Minish Cap
            1462,   // Castlevania
        ];

        return $activePlayers
            ->reject(function ($player) use ($mostPopularNewMemberGameIds) {
                return in_array($player['GameID'], $mostPopularNewMemberGameIds);
            })
            ->groupBy('GameID')
            ->map(function ($gameGroup) {
                return [
                    'ActivePlayerCount' => count($gameGroup),
                    'GameID' => $gameGroup[0]['GameID'],
                    'GameTitle' => $gameGroup[0]['GameTitle'],
                    'GameIcon' => $gameGroup[0]['GameIcon'], // Assuming all games of the same title have the same icon
                    'ConsoleName' => $gameGroup[0]['ConsoleName'], // Assuming all games of the same title belong to the same console
                ];
            })
            ->sortDesc()
            ->take(4)
            ->toArray();
    }

    private function useDevelopmentGameTitle(mixed $activePlayers): mixed
    {
        return $activePlayers->map(function ($activePlayer) {
            $isWorkingOnAchievements =
                str_contains($activePlayer['RichPresenceMsg'], 'Developing Achievements')
                || str_contains($activePlayer['RichPresenceMsg'], 'Fixing Achievements')
                || str_contains($activePlayer['RichPresenceMsg'], 'Inspecting Memory');

            if ($isWorkingOnAchievements) {
                $activePlayer['RichPresenceMsg'] .= ' for ' . $activePlayer['GameTitle'];
            }

            return $activePlayer;
        });
    }
}
