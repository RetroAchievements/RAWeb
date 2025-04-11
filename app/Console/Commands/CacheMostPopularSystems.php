<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CacheMostPopularSystems extends Command
{
    protected $signature = "ra:cache-most-popular-systems";
    protected $description = "Calculate the top systems by unique player count on the site and cache the value.";

    public function handle(): void
    {
        $this->info('Calculating top systems...');

        // Using sampling drops query execution time ~60%.
        $sampleRate = 0.7;

        // We use a raw query here because it's significantly faster than using Eloquent.
        $sql = <<<SQL
            SELECT 
                c.ID AS system_id,
                COUNT(DISTINCT pg.user_id) * (1 / {$sampleRate}) AS estimated_player_count
            FROM Console c
            JOIN GameData g ON c.ID = g.ConsoleID
            JOIN (
                SELECT 
                    game_id,
                    user_id,
                    last_played_at
                FROM player_games
                WHERE 
                    deleted_at IS NULL
                    AND last_played_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
                    AND RAND() < {$sampleRate}
            ) pg ON g.ID = pg.game_id
            WHERE 
                g.deleted_at IS NULL
                AND c.ID NOT IN (" . System::Hubs . ", " . System::Events . ", " . System::Standalones . ")
            GROUP BY c.ID
            ORDER BY estimated_player_count DESC
        SQL;

        $results = DB::select($sql);

        // Extract just the system IDs in order.
        $topSystemIds = array_map(fn ($row) => (int) $row->system_id, $results);

        Cache::put('top-systems', $topSystemIds, now()->addMonth());

        $this->newLine();
        $this->info("Done.");
    }
}
