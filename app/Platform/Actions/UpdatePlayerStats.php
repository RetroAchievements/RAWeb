<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Platform\Enums\PlayerStatType;
use App\Platform\Enums\UnlockMode;
use App\Platform\Events\PlayerStatsUpdated;
use App\Platform\Models\PlayerStat;
use App\Site\Models\User;

class UpdatePlayerStats
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
            ->where('GameData.Title', 'not like', '~Subset~%')
            ->where('GameData.Title', 'not like', '%[Subset%')
            ->where('GameData.Title', 'not like', '~Test Kit~%')
            ->where('GameData.Title', 'not like', '~Multi~%')
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

        $aggregatedPlayerStatValues = $this->calculateAggregatedGameBeatenHardcoreStatValues($playerBeatenHardcoreGames);
        $this->upsertAllPlayerStats($user, $aggregatedPlayerStatValues);
    }

    private function calculateAggregatedGameBeatenHardcoreStatValues(mixed $playerBeatenHardcoreGames): array
    {
        // We'll hold overall and per-console stat values.
        // type => [value, most recent hardcore beaten game id, beaten at timestamp]
        $statValues = [
            'overall' => [
                PlayerStatType::GamesBeatenHardcoreDemos => [0, null, null],
                PlayerStatType::GamesBeatenHardcoreHacks => [0, null, null],
                PlayerStatType::GamesBeatenHardcoreHomebrew => [0, null, null],
                PlayerStatType::GamesBeatenHardcorePrototypes => [0, null, null],
                PlayerStatType::GamesBeatenHardcoreRetail => [0, null, null],
                PlayerStatType::GamesBeatenHardcoreUnlicensed => [0, null, null],
            ],
        ];

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
            $gameKind = $this->deriveGameKindFromTitle($playerGame['game']['Title']);
            $statTypeKey = $gameKindToStatType[$gameKind] ?? PlayerStatType::GamesBeatenHardcoreRetail;

            // Update the overall aggregates.
            $statValues['overall'][$statTypeKey][0]++;
            $statValues['overall'][$statTypeKey][1] = $playerGame['game']['ID'];
            $statValues['overall'][$statTypeKey][2] = $playerGame['beaten_hardcore_at'];

            // Ensure there's an array entry for the console aggregates.
            if (!isset($statValues[$gameConsoleId])) {
                $statValues[$gameConsoleId] = [
                    PlayerStatType::GamesBeatenHardcoreDemos => [0, null, null],
                    PlayerStatType::GamesBeatenHardcoreHacks => [0, null, null],
                    PlayerStatType::GamesBeatenHardcoreHomebrew => [0, null, null],
                    PlayerStatType::GamesBeatenHardcorePrototypes => [0, null, null],
                    PlayerStatType::GamesBeatenHardcoreRetail => [0, null, null],
                    PlayerStatType::GamesBeatenHardcoreUnlicensed => [0, null, null],
                ];
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
        PlayerStat::where('user_id', $user->ID)->delete();
    }

    private function deriveGameKindFromTitle(string $gameTitle): string
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
        }

        return 'retail';
    }

    private function upsertAllPlayerStats(User $user, array $aggregatedPlayerStatValues): int
    {
        $updatedCount = 0;

        // Loop through each console ID in the aggregated values (including 'overall').
        foreach ($aggregatedPlayerStatValues as $aggregateSystemId => $systemStats) {
            // Check if it's the 'overall' key or a specific console ID.
            $systemId = $aggregateSystemId === 'overall' ? null : $aggregateSystemId;

            // Now, loop through each stat type for this system.
            foreach ($systemStats as $statType => $values) {
                // Extract the value and most recent game ID.
                [$value, $lastGameId, $updatedAt] = $values;

                if ($value > 0) {
                    $this->upsertPlayerStat($user, $statType, $value, $systemId, $lastGameId, $updatedAt);
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
        ?string $updatedAt
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
                'updated_at' => $updatedAt,
            ]
        );

        PlayerStatsUpdated::dispatch($user);
    }
}
