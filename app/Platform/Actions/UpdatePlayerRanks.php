<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Platform\Enums\RankingType;
use App\Platform\Enums\UnlockMode;
use App\Platform\Events\PlayerRanksUpdated;
use App\Platform\Models\Ranking;
use App\Site\Models\User;

class UpdatePlayerRanks
{
    public function execute(User $user): void
    {
        // If the user is untracked, wipe any rankings they
        // already have and then immediately bail. If/when they're
        // retracked, we can regenerate their rankings.
        if ($user->Untracked) {
            $this->clearExistingUntrackedRanks($user);

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
            ->orderBy('beaten_hardcore_at')
            ->get();

        $playerBeatenHardcoreGames->transform(function ($item) use ($user) {
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

        $aggregatedRankingValues = $this->calculateAggregatedGameBeatenHardcoreRankingValues($playerBeatenHardcoreGames);
        $this->upsertAllPlayerRanks($user, $aggregatedRankingValues);
    }

    private function calculateAggregatedGameBeatenHardcoreRankingValues(mixed $playerBeatenHardcoreGames): array
    {
        // We'll hold overall and per-console ranking values.
        // type => [value, most recent hardcore beaten game id, beaten at timestamp]
        $rankingValues = [
            'overall' => [
                RankingType::GamesBeatenHardcoreDemos => [0, null, null],
                RankingType::GamesBeatenHardcoreHacks => [0, null, null],
                RankingType::GamesBeatenHardcoreHomebrew => [0, null, null],
                RankingType::GamesBeatenHardcorePrototypes => [0, null, null],
                RankingType::GamesBeatenHardcoreRetail => [0, null, null],
                RankingType::GamesBeatenHardcoreUnlicensed => [0, null, null],
            ],
        ];

        $gameKindToRankingType = [
            'demo' => RankingType::GamesBeatenHardcoreDemos,
            'hack' => RankingType::GamesBeatenHardcoreHacks,
            'homebrew' => RankingType::GamesBeatenHardcoreHomebrew,
            'prototype' => RankingType::GamesBeatenHardcorePrototypes,
            'retail' => RankingType::GamesBeatenHardcoreRetail,
            'unlicensed' => RankingType::GamesBeatenHardcoreUnlicensed,
        ];

        foreach ($playerBeatenHardcoreGames as $playerGame) {
            $gameConsoleId = $playerGame['game']['ConsoleID'];
            $gameKind = $this->deriveGameKindFromTitle($playerGame['game']['Title']);
            $rankingTypeKey = $gameKindToRankingType[$gameKind] ?? RankingType::GamesBeatenHardcoreRetail;

            // Update the overall aggregates.
            $rankingValues['overall'][$rankingTypeKey][0]++;
            $rankingValues['overall'][$rankingTypeKey][1] = $playerGame['game']['ID'];
            $rankingValues['overall'][$rankingTypeKey][2] = $playerGame['beaten_hardcore_at'];

            // Ensure there's an array entry for the console aggregates.
            if (!isset($rankingValues[$gameConsoleId])) {
                $rankingValues[$gameConsoleId] = [
                    RankingType::GamesBeatenHardcoreDemos => [0, null, null],
                    RankingType::GamesBeatenHardcoreHacks => [0, null, null],
                    RankingType::GamesBeatenHardcoreHomebrew => [0, null, null],
                    RankingType::GamesBeatenHardcorePrototypes => [0, null, null],
                    RankingType::GamesBeatenHardcoreRetail => [0, null, null],
                    RankingType::GamesBeatenHardcoreUnlicensed => [0, null, null],
                ];
            }

            // Update the individual console aggregates.
            $rankingValues[$gameConsoleId][$rankingTypeKey][0]++;
            $rankingValues[$gameConsoleId][$rankingTypeKey][1] = $playerGame['game']['ID'];
            $rankingValues[$gameConsoleId][$rankingTypeKey][2] = $playerGame['beaten_hardcore_at'];
        }

        return $rankingValues;
    }

    private function clearExistingUntrackedRanks(User $user): void
    {
        Ranking::where('user_id', $user->ID)->delete();
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

    private function upsertAllPlayerRanks(User $user, array $aggregatedRankingValues): int
    {
        $updatedCount = 0;

        // Loop through each console ID in the aggregated values (including 'overall').
        foreach ($aggregatedRankingValues as $aggregateSystemId => $systemRankings) {
            // Check if it's the 'overall' key or a specific console ID.
            $systemId = $aggregateSystemId === 'overall' ? null : $aggregateSystemId;

            // Now, loop through each ranking type for this system.
            foreach ($systemRankings as $rankingType => $values) {
                // Extract the value and most recent game ID.
                [$value, $gameId, $updatedAt] = $values;

                if ($value > 0) {
                    $this->upsertPlayerRank($user, $rankingType, $value, $systemId, $gameId, $updatedAt);
                    $updatedCount++;
                }
            }
        }

        return $updatedCount;
    }

    private function upsertPlayerRank(User $user, string $rankingType, int $value, ?int $systemId, ?int $gameId, ?string $updatedAt): void
    {
        Ranking::updateOrCreate(
            [
                'user_id' => $user->ID,
                'system_id' => $systemId,
                'type' => $rankingType,
            ],
            [
                'game_id' => $gameId,
                'value' => $value,
                'updated_at' => $updatedAt,
            ]
        );

        PlayerRanksUpdated::dispatch($user);
    }
}
