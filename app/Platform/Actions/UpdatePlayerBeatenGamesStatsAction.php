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
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UpdatePlayerBeatenGamesStatsAction
{
    private const BEATEN_GAMES_STAT_TYPES = [
        PlayerStatType::GamesBeatenHardcoreDemos,
        PlayerStatType::GamesBeatenHardcoreHacks,
        PlayerStatType::GamesBeatenHardcoreHomebrew,
        PlayerStatType::GamesBeatenHardcorePrototypes,
        PlayerStatType::GamesBeatenHardcoreRetail,
        PlayerStatType::GamesBeatenHardcoreUnlicensed,
    ];

    public function execute(User $user): void
    {
        // If the user is untracked, wipe any stats they
        // already have and then immediately bail. If/when
        // they're retracked, we can regenerate their stats.
        if ($user->unranked_at !== null) {
            $this->clearExistingUntrackedStats($user);

            return;
        }

        // Get existing stats to ensure we maintain entries for all previously tracked systems.
        $existingStats = PlayerStat::where('user_id', $user->id)->get();

        $playerBeatenHardcoreGames = $user
            ->playerBadges()
            ->where('award_type', AwardType::GameBeaten)
            ->where('award_tier', UnlockMode::Hardcore)
            ->join('games', 'games.id', '=', 'award_key')
            ->select(
                'games.id as game_id',
                'games.system_id',
                'games.title',
                'user_awards.awarded_at as beaten_hardcore_at'
            )
            ->where(DB::raw('games.title'), 'not like', '%[Subset%')
            ->where(DB::raw('games.title'), 'not like', '~Test Kit~%')
            ->orderBy('beaten_hardcore_at')
            ->get();

        $playerBeatenHardcoreGames = $playerBeatenHardcoreGames->map(function ($item) use ($user) {
            return [
                'user_id' => $user->id,
                'game_id' => $item->game_id,
                'beaten_hardcore_at' => $item->beaten_hardcore_at,
                'game' => [
                    'id' => $item->game_id,
                    'system_id' => $item->system_id,
                    'title' => $item->title,
                ],
            ];
        });

        $aggregatedPlayerStatValues = $this->calculateAggregatedGameBeatenHardcoreStatValues($playerBeatenHardcoreGames, $existingStats);
        $this->writeAllPlayerStats($user, $aggregatedPlayerStatValues, $existingStats);
    }

    /**
     * @param Collection<int, PlayerStat> $existingStats
     */
    private function calculateAggregatedGameBeatenHardcoreStatValues(
        mixed $playerBeatenHardcoreGames,
        Collection $existingStats,
    ): array {
        // type => [value, most recent hardcore beaten game id, beaten at timestamp]
        $getInitializedStats = function () {
            return array_fill_keys(self::BEATEN_GAMES_STAT_TYPES, [0, null, null]);
        };

        // We'll hold overall and per-console stat values.
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
            $gameConsoleId = $playerGame['game']['system_id'];
            $gameKind = $this->determineGameKind($playerGame['game']['title'], $gameConsoleId);
            $statTypeKey = $gameKindToStatType[$gameKind] ?? PlayerStatType::GamesBeatenHardcoreRetail;

            // Update the overall aggregates.
            $statValues['overall'][$statTypeKey][0]++;
            $statValues['overall'][$statTypeKey][1] = $playerGame['game']['id'];
            $statValues['overall'][$statTypeKey][2] = $playerGame['beaten_hardcore_at'];

            // Ensure there's an array entry for the console aggregates.
            if (!isset($statValues[$gameConsoleId])) {
                $statValues[$gameConsoleId] = $getInitializedStats();
            }

            // Update the individual console aggregates.
            $statValues[$gameConsoleId][$statTypeKey][0]++;
            $statValues[$gameConsoleId][$statTypeKey][1] = $playerGame['game']['id'];
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
        }

        // Some consoles were never sold in stores and are considered "homebrew".
        // Their games fall back to "homebrew" rather than "retail".
        if (System::isHomebrewSystem($gameConsoleId)) {
            return 'homebrew';
        }

        return 'retail';
    }

    /**
     * Diff the desired stat rows against the rows the player already has, then
     * write only the differences in a bounded number of bulk statements.
     *
     * @param Collection<int, PlayerStat> $existingStats
     */
    private function writeAllPlayerStats(
        User $user,
        array $aggregatedPlayerStatValues,
        Collection $existingStats,
    ): void {
        [$existingStatsMap, $duplicateIdsToDelete] = $this->buildExistingStatsMap($existingStats);
        [$rowsToInsert, $rowsToUpdate] = $this->buildWriteBuckets($user, $aggregatedPlayerStatValues, $existingStatsMap);

        if (!$duplicateIdsToDelete && !$rowsToInsert && !$rowsToUpdate) {
            return;
        }

        // Sort every payload ascending so concurrent writers acquire locks in the
        // same order, which is what keeps them from deadlocking each other.
        sort($duplicateIdsToDelete);
        usort($rowsToInsert, fn ($a, $b) => [$a['system_id'] ?? 0, $a['type']] <=> [$b['system_id'] ?? 0, $b['type']]);
        usort($rowsToUpdate, fn ($a, $b) => $a['id'] <=> $b['id']);

        // Retry on a deadlock, as several queue workers can write this table at once.
        DB::transaction(function () use ($duplicateIdsToDelete, $rowsToInsert, $rowsToUpdate) {
            // Discard duplicates first.
            if ($duplicateIdsToDelete) {
                PlayerStat::whereIn('id', $duplicateIdsToDelete)->delete();
            }

            if ($rowsToInsert) {
                PlayerStat::insert($rowsToInsert);
            }

            if ($rowsToUpdate) {
                PlayerStat::upsert(
                    $rowsToUpdate,
                    ['id'],
                    ['last_game_id', 'value', 'stat_updated_at', 'updated_at']
                );
            }
        }, attempts: 3);

        PlayerBeatenGamesStatsUpdated::dispatch($user);
    }

    /**
     * Route every desired stat row to the insert bucket, the update bucket, or
     * neither. A row whose stored values already match is not written at all.
     *
     * @param array<string, PlayerStat> $existingStatsMap
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private function buildWriteBuckets(
        User $user,
        array $aggregatedPlayerStatValues,
        array $existingStatsMap,
    ): array {
        $now = now();
        $rowsToInsert = [];
        $rowsToUpdate = [];

        // Loop through each console ID in the aggregated values (including 'overall').
        foreach ($aggregatedPlayerStatValues as $aggregateSystemId => $systemStats) {
            // Check if it's the 'overall' key or a specific console ID.
            $systemId = $aggregateSystemId === 'overall' ? null : (int) $aggregateSystemId;

            // Now, loop through each stat type for this system.
            foreach ($systemStats as $statType => $values) {
                // Extract the value and most recent game ID.
                [$value, $lastGameId, $statUpdatedAt] = $values;

                $existingStat = $existingStatsMap[$this->buildStatKey($systemId, $statType)] ?? null;

                if ($value === 0 && $existingStat === null) {
                    continue;
                }

                if ($existingStat !== null && $this->isStatUnchanged($existingStat, $value, $lastGameId, $statUpdatedAt)) {
                    continue;
                }

                $row = [
                    'user_id' => $user->id,
                    'system_id' => $systemId,
                    'type' => $statType,
                    'last_game_id' => $lastGameId,
                    'value' => $value,
                    'stat_updated_at' => $statUpdatedAt,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if ($existingStat === null) {
                    $rowsToInsert[] = $row;
                } else {
                    $rowsToUpdate[] = ['id' => $existingStat->id] + $row;
                }
            }
        }

        return [$rowsToInsert, $rowsToUpdate];
    }

    /**
     * Key the player's existing rows by system and type for lookup during the diff.
     *
     * @param Collection<int, PlayerStat> $existingStats
     * @return array{0: array<string, PlayerStat>, 1: array<int, int>}
     */
    private function buildExistingStatsMap(Collection $existingStats): array
    {
        $existingStatsMap = [];
        $duplicateIdsToDelete = [];

        foreach ($existingStats as $stat) {
            $key = $this->buildStatKey($stat->system_id, $stat->type);
            $incumbent = $existingStatsMap[$key] ?? null;

            if ($incumbent === null) {
                $existingStatsMap[$key] = $stat;

                continue;
            }

            if (!in_array($stat->type, self::BEATEN_GAMES_STAT_TYPES, true)) {
                continue;
            }

            if ($stat->id < $incumbent->id) {
                $existingStatsMap[$key] = $stat;
                $duplicateIdsToDelete[] = $incumbent->id;
            } else {
                $duplicateIdsToDelete[] = $stat->id;
            }
        }

        return [$existingStatsMap, $duplicateIdsToDelete];
    }

    private function buildStatKey(?int $systemId, string $statType): string
    {
        return ($systemId ?? 'overall') . '|' . $statType;
    }

    private function isStatUnchanged(
        PlayerStat $existingStat,
        int $value,
        ?int $lastGameId,
        ?string $statUpdatedAt,
    ): bool {
        $existingLastGameId = $existingStat->last_game_id === null ? null : (int) $existingStat->last_game_id;

        return
            (int) $existingStat->value === $value
            && $existingLastGameId === ($lastGameId === null ? null : (int) $lastGameId)
            && $this->normalizeTimestamp($existingStat->stat_updated_at) === $this->normalizeTimestamp($statUpdatedAt);
    }

    /**
     * The model casts stat_updated_at to Carbon while the aggregation carries the
     * raw string it read. Both sides need the same shape or every row looks changed.
     */
    private function normalizeTimestamp(mixed $timestamp): ?string
    {
        if ($timestamp === null || $timestamp === '') {
            return null;
        }

        return Carbon::parse($timestamp)->toDateTimeString();
    }
}
