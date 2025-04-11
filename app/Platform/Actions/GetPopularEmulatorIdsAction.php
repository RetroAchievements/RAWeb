<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\System;
use Illuminate\Support\Facades\DB;

class GetPopularEmulatorIdsAction
{
    public function execute(?System $system = null): array
    {
        if ($system !== null && !$system->active) {
            return [];
        }

        return $system
            ? $this->getPopularEmulatorsForSystem($system)
            : $this->getPopularEmulatorsOverall();
    }

    private function getPopularEmulatorsForSystem(System $system): array
    {
        // We use a raw query here because it's significantly faster than using Eloquent.
        $sql = <<<SQL
            WITH sampled_sessions AS (
                SELECT 
                    ps.id,
                    ps.user_id,
                    ps.game_id,
                    ps.user_agent
                FROM player_sessions ps
                JOIN GameData g ON ps.game_id = g.ID
                WHERE 
                    g.ConsoleID = {$system->id}
                    AND ps.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
                    AND ps.user_id IS NOT NULL
                    AND RAND() < 0.2
            )

            SELECT 
                e.id AS emulator_id,
                e.name AS emulator_name,
                COUNT(DISTINCT ss.user_id) AS unique_player_count
            FROM sampled_sessions ss
            JOIN emulator_user_agents eua ON ss.user_agent LIKE CONCAT('%', eua.client, '%')
            JOIN emulators e ON eua.emulator_id = e.id
            JOIN system_emulators se ON e.id = se.emulator_id AND se.system_id = {$system->id}
            GROUP BY e.id
            ORDER BY unique_player_count DESC
        SQL;

        $results = DB::select($sql);

        // Extract just the emulator IDs in order.
        return array_map(fn ($row) => $row->emulator_id, $results);
    }

    private function getPopularEmulatorsOverall(): array
    {
        // We use a raw query here because it's significantly faster than using Eloquent.
        $sql = <<<SQL
            WITH sampled_sessions AS (
                SELECT 
                    ps.id,
                    ps.user_id,
                    ps.game_id,
                    ps.user_agent
                FROM player_sessions ps
                WHERE 
                    ps.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
                    AND ps.user_id IS NOT NULL
                    AND RAND() < 0.2
            )

            SELECT 
                e.id AS emulator_id,
                e.name AS emulator_name,
                COUNT(DISTINCT ss.user_id) AS unique_player_count
            FROM sampled_sessions ss
            JOIN emulator_user_agents eua ON ss.user_agent LIKE CONCAT('%', eua.client, '%')
            JOIN emulators e ON eua.emulator_id = e.id
            GROUP BY e.id
            ORDER BY unique_player_count DESC
        SQL;

        $results = DB::select($sql);

        // Extract just the emulator IDs in order.
        return array_map(fn ($row) => $row->emulator_id, $results);
    }
}
