<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\PlayerStat;
use App\Models\PlayerStatRanking;
use App\Models\System;
use App\Platform\Enums\PlayerStatRankingKind;
use App\Platform\Enums\PlayerStatType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UpdateBeatenGamesLeaderboardAction
{
    /**
     * If $systemId is null, calculate the overall metrics which encompass all systems.
     */
    public function execute(?int $systemId, PlayerStatRankingKind $kind): void
    {
        $includedTypes = $this->getIncludedTypes($kind, $systemId);
        if (empty($includedTypes)) {
            return;
        }

        // Use a Redis lock per system+kind to prevent deadlocks when
        // multiple jobs for the same system and kind run concurrently.
        // Different kinds can safely run in parallel since they operate on distinct rows.
        $lockKey = 'player-stat-rankings:' . ($systemId ?? 'overall') . ':' . $kind->value;
        Cache::lock($lockKey, 120)->block(90, function () use ($systemId, $kind, $includedTypes) {
            $this->executeWithDirectInsert($systemId, $kind, $includedTypes);
        });
    }

    private function deleteExistingRankings(?int $systemId, PlayerStatRankingKind $kind): void
    {
        PlayerStatRanking::query()
            ->when(
                $systemId !== null,
                fn ($q) => $q->where('system_id', $systemId),
                fn ($q) => $q->whereNull('system_id')
            )
            ->where('kind', $kind->value)
            ->delete();
    }

    /**
     * @param array<string> $includedTypes
     */
    private function executeWithDirectInsert(?int $systemId, PlayerStatRankingKind $kind, array $includedTypes): void
    {
        $typesPlaceholder = implode("', '", $includedTypes);

        $aggregateSubquery = PlayerStat::query()
            ->selectRaw('user_id, SUM(value) AS total, MAX(stat_updated_at) AS last_affected_at')
            ->when(
                $systemId !== null,
                fn ($q) => $q->where('system_id', $systemId),
                fn ($q) => $q->whereNull('system_id')
            )
            ->whereIn('type', $includedTypes)
            ->groupBy('user_id')
            ->havingRaw('SUM(value) > 0');

        // RANK/ROW_NUMBER require selectRaw since Eloquent doesn't support window functions natively.
        $selectQuery = PlayerStat::query()
            ->selectRaw(
                "sub.user_id,
                ? as system_id,
                ? as `kind`,
                sub.total,
                RANK() OVER (ORDER BY sub.total DESC) as rank_number,
                ROW_NUMBER() OVER (ORDER BY sub.total DESC, sub.last_affected_at ASC) as `row_number`,
                MAX(CASE WHEN player_stats.type IN ('{$typesPlaceholder}') THEN player_stats.last_game_id ELSE NULL END) AS last_game_id,
                MAX(CASE WHEN player_stats.type IN ('{$typesPlaceholder}') THEN player_stats.stat_updated_at ELSE NULL END) as last_affected_at,
                CURRENT_TIMESTAMP as created_at",
                [$systemId, $kind->value]
            )
            ->joinSub($aggregateSubquery, 'sub', function ($join) use ($systemId) {
                $join->on('sub.user_id', '=', 'player_stats.user_id')
                    ->on('sub.last_affected_at', '=', 'player_stats.stat_updated_at');

                if ($systemId !== null) {
                    $join->where('player_stats.system_id', '=', $systemId);
                } else {
                    $join->whereNull('player_stats.system_id');
                }
            })
            ->whereIn('player_stats.type', $includedTypes)
            ->groupBy('sub.user_id', 'sub.total', 'sub.last_affected_at');

        // Delete existing rows and insert new ones in a transaction.
        // We'll delete stuff first because MySQL/MariaDB treats NULL != NULL in unique constraints,
        // so ON DUPLICATE KEY UPDATE doesn't work reliably for overall leaderboards.
        DB::transaction(function () use ($systemId, $kind, $selectQuery) {
            $this->deleteExistingRankings($systemId, $kind);

            PlayerStatRanking::insertUsing(
                [
                    'user_id',
                    'system_id',
                    'kind',
                    'total',
                    'rank_number',
                    'row_number',
                    'last_game_id',
                    'last_affected_at',
                    'created_at',
                ],
                $selectQuery
            );
        });
    }

    /**
     * @return array<string>
     */
    private function getIncludedTypes(PlayerStatRankingKind $kind, ?int $systemId): array
    {
        $isHomebrewSystem = $systemId !== null && System::isHomebrewSystem($systemId);

        return match ($kind) {
            PlayerStatRankingKind::RetailBeaten => $isHomebrewSystem
                ? [] // Homebrew systems have no retail games.
                : [
                    PlayerStatType::GamesBeatenHardcoreRetail,
                    PlayerStatType::GamesBeatenHardcoreUnlicensed,
                ],
            PlayerStatRankingKind::HomebrewBeaten => [
                PlayerStatType::GamesBeatenHardcoreHomebrew,
            ],
            PlayerStatRankingKind::HacksBeaten => [
                PlayerStatType::GamesBeatenHardcoreHacks,
            ],
            PlayerStatRankingKind::AllBeaten => $isHomebrewSystem
                ? [
                    PlayerStatType::GamesBeatenHardcoreUnlicensed,
                    PlayerStatType::GamesBeatenHardcoreHomebrew,
                    PlayerStatType::GamesBeatenHardcoreHacks,
                    PlayerStatType::GamesBeatenHardcorePrototypes,
                    PlayerStatType::GamesBeatenHardcoreDemos,
                ]
                : [
                    PlayerStatType::GamesBeatenHardcoreRetail,
                    PlayerStatType::GamesBeatenHardcoreUnlicensed,
                    PlayerStatType::GamesBeatenHardcoreHomebrew,
                    PlayerStatType::GamesBeatenHardcoreHacks,
                    PlayerStatType::GamesBeatenHardcorePrototypes,
                    PlayerStatType::GamesBeatenHardcoreDemos,
                ],
        };
    }
}
