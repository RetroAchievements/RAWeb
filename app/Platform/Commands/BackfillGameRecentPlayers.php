<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\Game;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

class BackfillGameRecentPlayers extends Command
{
    protected $signature = 'ra:platform:game:backfill-recent-players
                            {gameIds? : Optional comma-separated list of game IDs}';
    protected $description = "Backfill game_recent_players table from existing player sessions";

    public function handle(): void
    {
        $query = Game::query();

        if ($this->argument('gameIds')) {
            $gameIds = collect(explode(',', $this->argument('gameIds')))->map(fn ($id) => (int) $id);
            $query->whereIn('ID', $gameIds);
        }

        $totalGames = $query->count();
        if ($totalGames === 0) {
            $this->info('No games found.');

            return;
        }

        $this->info("Processing {$totalGames} games...");
        $this->info("Creating job batches...");

        $batchCount = 0;
        $query->chunk(1000, function ($games) use (&$batchCount) {
            $batchJobs = [];

            foreach ($games as $game) {
                $batchJobs[] = function () use ($game) {
                    // Get the 20 most recent players for this game.
                    $query = <<<SQL
                        INSERT INTO game_recent_players (game_id, user_id, rich_presence, rich_presence_updated_at)
                        SELECT 
                            game_id,
                            user_id,
                            rich_presence,
                            rich_presence_updated_at
                        FROM (
                            SELECT 
                                ps.game_id,
                                ps.user_id,
                                ps.rich_presence,
                                ps.rich_presence_updated_at,
                                ROW_NUMBER() OVER (ORDER BY ps.rich_presence_updated_at DESC) as recent_rank
                            FROM (
                                SELECT 
                                    game_id,
                                    user_id,
                                    rich_presence,
                                    rich_presence_updated_at,
                                    ROW_NUMBER() OVER (PARTITION BY user_id ORDER BY rich_presence_updated_at DESC) as rn
                                FROM player_sessions
                                WHERE game_id = ?
                            ) ps
                            WHERE ps.rn = 1
                        ) ranked
                        WHERE recent_rank <= 20
                        ON DUPLICATE KEY UPDATE
                            rich_presence = VALUES(rich_presence),
                            rich_presence_updated_at = VALUES(rich_presence_updated_at)
                    SQL;

                    DB::statement($query, [$game->id]);
                };
            }

            if (!empty($batchJobs)) {
                Bus::batch($batchJobs)->onQueue('player-game-metrics-batch')->dispatch();
                $batchCount++;
            }
        });

        $this->info("Dispatched {$totalGames} jobs in {$batchCount} batches to backfill game recent players.");
    }
}
