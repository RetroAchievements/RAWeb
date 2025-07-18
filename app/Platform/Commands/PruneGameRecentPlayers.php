<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneGameRecentPlayers extends Command
{
    protected $signature = 'ra:platform:game:prune-recent-players';
    protected $description = "Prune game_recent_players table to keep only the 20 most recent players per game";

    public function handle(): void
    {
        $this->info('Pruning game_recent_players table...');

        // Delete records that are not in the top 20 most recent for each game.
        $query = <<<SQL
            DELETE grp FROM game_recent_players grp
            INNER JOIN (
                SELECT 
                    id,
                    ROW_NUMBER() OVER (PARTITION BY game_id ORDER BY rich_presence_updated_at DESC) as row_num
                FROM game_recent_players
            ) ranked ON grp.id = ranked.id
            WHERE ranked.row_num > 20
        SQL;

        $deletedRows = DB::affectingStatement($query);

        $this->info("Pruned {$deletedRows} records.");
    }
}
