<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Models\PlayerStat;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\PlayerStatType;
use App\Platform\Enums\UnlockMode;
use App\Platform\Events\PlayerBeatenGamesStatsUpdated;
use Illuminate\Support\Collection;

class UpdatePlayerBeatenGamesStatsAction
{
    public function execute(User $user): void
    {
        // If the user is untracked, wipe any stats they
        // already have and then immediately bail. If/when
        // they're retracked, we can regenerate their stats.
        if ($user->Untracked) {
            $this->clearExistingUntrackedStats($user);

            return;
        }

        // Get existing stats to ensure we maintain entries for all previously tracked systems.
        $existingStats = PlayerStat::where('user_id', $user->id)->get();

        $playerBeatenHardcoreGames = $user
            ->playerBadges()
            ->where('AwardType', AwardType::GameBeaten)
            ->where('AwardDataExtra', UnlockMode::Hardcore)
            ->join('GameData', 'GameData.ID', '=', 'AwardData')
            ->select(
                'GameData.ID as game_id',
                'GameData.ConsoleID',
                'GameData.Title',
                'SiteAwards.AwardDate as beaten_hardcore_at'
            )
            ->where('GameData.Title', 'not like', '%[Subset%')
            ->where('GameData.Title', 'not like', '~Test Kit~%')
            ->orderBy('beaten_hardcore_at')
            ->get();

        $playerBeatenHardcoreGames = $playerBeatenHardcoreGames->map(function ($item) use ($user) {
            return [
                'user_id' => $user->id,
                'game_id' => $item->game_id,
                'beaten_hardcore_at' => $item->beaten_hardcore_at,
                'game' => [
                    'ID' => $item->game_id,
                    'ConsoleID' => $item->ConsoleID,
                    'Title' => $item->Title,
                ],
            ];
        });

        $aggregatedPlayerStatValues = $this->calculateAggregatedGameBeatenHardcoreStatValues($playerBeatenHardcoreGames, $existingStats);
        $this->upsertAllPlayerStats($user, $aggregatedPlayerStatValues, $existingStats);
    }

    /**
     * @param Collection<int, PlayerStat> $existingStats
     */
    private function calculateAggregatedGameBeatenHardcoreStatValues(
        mixed $playerBeatenHardcoreGames,
        Collection $existingStats,
    ): array {
        $getInitializedStats = function () {
            return [
                PlayerStatType::GamesBeatenHardcoreDemos => [0, null, null],
                PlayerStatType::GamesBeatenHardcoreHacks => [0, null, null],
                PlayerStatType::GamesBeatenHardcoreHomebrew => [0, null, null],
                PlayerStatType::GamesBeatenHardcorePrototypes => [0, null, null],
                PlayerStatType::GamesBeatenHardcoreRetail => [0, null, null],
                PlayerStatType::GamesBeatenHardcoreUnlicensed => [0, null, null],
            ];
        };

        // We'll hold overall and per-console stat values.
        // type => [value, most recent hardcore beaten game id, beaten at timestamp]
        $statValues = [
            'overall' => $getInitializedStats(),
        ];

        // Initialize entries for all systems that previously had stats.
        foreach ($existingStats as $stat) {
            if ($stat->system_id !== null && !isset($statValues[$stat->system_id])) {
                $statValues[$stat->system_id] = $getInitializedStats();
            }
        }

        $gameKindToStatType = [
            'demo' => PlayerStatType::GamesBeatenHardcoreDemos,
            'hack' => PlayerStatType::GamesBeatenHardcoreHacks,
            'homebrew' => PlayerStatType::GamesBeatenHardcoreHomebrew,
            'prototype' => PlayerStatType::GamesBeatenHardcorePrototypes,
            'retail' => PlayerStatType::GamesBeatenHardcoreRetail,
            'unlicensed' => PlayerStatType::GamesBeatenHardcoreUnlicensed,
        ];

        foreach ($playerBeatenHardcoreGames as $playerGame) {
            $gameConsoleId = $playerGame['game']['ConsoleID'];
            $gameKind = $this->determineGameKind($playerGame['game']['Title'], $gameConsoleId);
            $statTypeKey = $gameKindToStatType[$gameKind] ?? PlayerStatType::GamesBeatenHardcoreRetail;

            // Update the overall aggregates.
            $statValues['overall'][$statTypeKey][0]++;
            $statValues['overall'][$statTypeKey][1] = $playerGame['game']['ID'];
            $statValues['overall'][$statTypeKey][2] = $playerGame['beaten_hardcore_at'];

            // Ensure there's an array entry for the console aggregates.
            if (!isset($statValues[$gameConsoleId])) {
                $statValues[$gameConsoleId] = $getInitializedStats();
            }

            // Update the individual console aggregates.
            $statValues[$gameConsoleId][$statTypeKey][0]++;
            $statValues[$gameConsoleId][$statTypeKey][1] = $playerGame['game']['ID'];
            $statValues[$gameConsoleId][$statTypeKey][2] = $playerGame['beaten_hardcore_at'];
        }

        return $statValues;
    }

    private function clearExistingUntrackedStats(User $user): void
    {
        PlayerStat::where('user_id', $user->id)->delete();
    }

    private function determineGameKind(string $gameTitle, int $gameConsoleId): string
    {
        $sanitizedTitle = mb_strtolower($gameTitle);
        $gameKinds = [
            '~demo~' => 'demo',
            '~prototype~' => 'prototype',
            '~unlicensed~' => 'unlicensed',
            '~homebrew~' => 'homebrew',
            '~hack~' => 'hack',
        ];

        foreach ($gameKinds as $keyword => $kind) {
            if (str_contains($sanitizedTitle, $keyword)) {
                return $kind;
            }

            // Some consoles were never sold in stores and are considered "homebrew".
            // Their games fall back to "homebrew" rather than "retail".
            if (System::isHomebrewSystem($gameConsoleId)) {
                return 'homebrew';
            }
        }

        return 'retail';
    }

    /**
     * @param Collection<int, PlayerStat> $existingStats
     */
    private function upsertAllPlayerStats(
        User $user,
        array $aggregatedPlayerStatValues,
        Collection $existingStats,
    ): int {
        $updatedCount = 0;

        // Create a map for quick lookups using system_id and type as the key.
        $existingStatsMap = [];
        foreach ($existingStats as $stat) {
            $key = ($stat->system_id ?? 'overall') . '|' . $stat->type;
            $existingStatsMap[$key] = true;
        }

        // Loop through each console ID in the aggregated values (including 'overall').
        foreach ($aggregatedPlayerStatValues as $aggregateSystemId => $systemStats) {
            // Check if it's the 'overall' key or a specific console ID.
            $systemId = $aggregateSystemId === 'overall' ? null : $aggregateSystemId;

            // Now, loop through each stat type for this system.
            foreach ($systemStats as $statType => $values) {
                // Extract the value and most recent game ID.
                [$value, $lastGameId, $statUpdatedAt] = $values;

                // Check if this stat combination exists in our map.
                $key = ($systemId ?? 'overall') . '|' . $statType;

                if ($value > 0 || isset($existingStatsMap[$key])) {
                    $this->upsertPlayerStat($user, $statType, $value, $systemId, $lastGameId, $statUpdatedAt);
                    $updatedCount++;
                }
            }
        }

        return $updatedCount;
    }

    private function upsertPlayerStat(
        User $user,
        string $statType,
        int $value,
        ?int $systemId,
        ?int $lastGameId,
        ?string $statUpdatedAt
    ): void {
        PlayerStat::updateOrCreate(
            [
                'user_id' => $user->ID,
                'system_id' => $systemId,
                'type' => $statType,
            ],
            [
                'last_game_id' => $lastGameId,
                'value' => $value,
                'stat_updated_at' => $statUpdatedAt,
            ]
        );

        PlayerBeatenGamesStatsUpdated::dispatch($user);
    }
}
