<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Community\Actions\LoadThinActivePlayersListAction;
use App\Community\Enums\GameActivitySnapshotType;
use App\Community\Enums\TrendingReason;
use App\Models\Game;
use App\Models\GameActivitySnapshot;
use App\Models\System;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UpdateGameActivitySnapshots extends Command
{
    protected $signature = 'ra:update-game-activity-snapshots
        {--date= : Target date to analyze (eg: 2025-12-20). Defaults to now.}';
    protected $description = 'Update the game activity snapshots for trending and popular games.';

    private Carbon $targetDate;

    public function handle(): void
    {
        $dateOption = $this->option('date');
        $isHistoricalBackfill = $dateOption !== null;

        $this->targetDate = $isHistoricalBackfill ? Carbon::parse($dateOption)->midDay() : now();

        $this->info('Updating game activity snapshots...');
        $this->info("Target date: {$this->targetDate->format('Y-m-d H:i:s')}");

        $this->updateTrendingGames();

        if ($isHistoricalBackfill) {
            $this->generateFakePopularGames();
        } else {
            $this->updatePopularGames();
        }

        $this->newLine();
        $this->info('Done.');
    }

    /**
     * Calculate and store trending games using breakout detection.
     *
     * A naive "trending" algorithm that just shows games with the most active players
     * means consistently popular games (like Final Fantasy XI with 300+ players)
     * dominate the list perpetually. That's "popular", not "trending."
     *
     * True trending means "rate of change" - games experiencing unusual activity relative
     * to their baseline. A game with 400 new players when it normally gets 25/day (16x spike)
     * is genuinely trending. A game with 500 active players when it always has 500 is just popular.
     *
     * Core Formula:
     *   trend = (new_players_48h + k) * 7 / (new_players_baseline_14d + k)
     *   score = trend * sqrt(new_players_48h) * evergreen_penalty
     *
     * Parameters:
     * - k=10 Bayesian smoothing: Prevents small-sample noise. A game going from 2 to 6 players
     *   (technically 21x!) gets dampened to ~3x. A game going 174 to 407 stays at 16x because
     *   the volume is real.
     * - Minimum 10 new players in 48h: Filters out statistical noise from tiny games.
     * - 1.5x trend threshold: Must be 50% above baseline to qualify as "trending."
     * - Evergreen penalty: Games with huge player bases get dampened because spikes are
     *   less noteworthy (>30k: 0.3x, >15k: 0.5x, >5k: 0.75x).
     *
     * Filters:
     * - No event games.
     * - No subset games.
     * - Set must have been published 48+ hours ago because we require baseline data.
     */
    private function updateTrendingGames(): void
    {
        $this->info('Calculating trending games...');

        $eventsSystemId = System::Events;
        $targetDateStr = $this->targetDate->format('Y-m-d H:i:s');

        $sql = <<<SQL
            SELECT
                metrics.game_id,
                g.players_total,
                metrics.new_recent,
                metrics.new_baseline,
                ROUND((metrics.new_recent + 10) * 7.0 / (metrics.new_baseline + 10), 2) as trend,
                ROUND(
                    ((metrics.new_recent + 10) * 7.0 / (metrics.new_baseline + 10))
                    * SQRT(metrics.new_recent)
                    * CASE
                        WHEN g.players_total > 30000 THEN 0.3
                        WHEN g.players_total > 15000 THEN 0.5
                        WHEN g.players_total > 5000 THEN 0.75
                        ELSE 1.0
                      END,
                    2
                ) as score,
                (SELECT MAX(claims.finished_at)
                 FROM achievement_set_claims claims
                 WHERE claims.game_id = metrics.game_id
                   AND claims.status = 'complete'
                   AND claims.set_type = 'new_set'
                   AND claims.finished_at BETWEEN '{$targetDateStr}' - INTERVAL 14 DAY AND '{$targetDateStr}' - INTERVAL 48 HOUR
                ) as new_set_date,
                (SELECT MAX(claims.finished_at)
                 FROM achievement_set_claims claims
                 WHERE claims.game_id = metrics.game_id
                   AND claims.status = 'complete'
                   AND claims.set_type = 'revision'
                   AND claims.finished_at >= '{$targetDateStr}' - INTERVAL 14 DAY
                ) as revision_date
            FROM (
                SELECT
                    game_id,
                    SUM(CASE WHEN created_at >= '{$targetDateStr}' - INTERVAL 48 HOUR THEN 1 ELSE 0 END) as new_recent,
                    SUM(CASE WHEN created_at >= '{$targetDateStr}' - INTERVAL 16 DAY
                             AND created_at < '{$targetDateStr}' - INTERVAL 48 HOUR THEN 1 ELSE 0 END) as new_baseline
                FROM player_games
                WHERE created_at >= '{$targetDateStr}' - INTERVAL 16 DAY
                  AND created_at <= '{$targetDateStr}'
                GROUP BY game_id
                HAVING new_recent >= 10
            ) metrics
            JOIN games g ON g.id = metrics.game_id
            WHERE g.system_id != {$eventsSystemId}
              AND g.title NOT LIKE '%[Subset%'
              AND g.deleted_at IS NULL
              AND g.achievements_published > 0
              AND EXISTS (
                  SELECT 1 FROM achievement_set_claims claims
                  WHERE claims.game_id = metrics.game_id
                    AND claims.status = 'complete'
                    AND claims.set_type = 'new_set'
                    AND claims.finished_at <= '{$targetDateStr}' - INTERVAL 48 HOUR
              )
            HAVING trend >= 1.5
            ORDER BY score DESC
            LIMIT 10
        SQL;

        $results = DB::select($sql);

        foreach ($results as $row) {
            $trendingReason = $this->determineTrendingReason(
                newSetDate: $row->new_set_date,
                revisionDate: $row->revision_date,
                playersTotal: (int) $row->players_total,
                trend: (float) $row->trend,
            );

            GameActivitySnapshot::create([
                'game_id' => $row->game_id,
                'type' => GameActivitySnapshotType::Trending,
                'score' => $row->score,
                'trend_multiplier' => $row->trend,
                'trending_reason' => $trendingReason->value,
            ]);
        }

        $this->info('Stored ' . count($results) . ' trending games.');
    }

    private function updatePopularGames(): void
    {
        $this->info('Calculating popular games...');

        $gameCounts = $this->getCurrentPopularGames();

        arsort($gameCounts);

        $topGames = array_slice($gameCounts, 0, 10, true);

        foreach ($topGames as $gameId => $playerCount) {
            GameActivitySnapshot::create([
                'game_id' => $gameId,
                'type' => GameActivitySnapshotType::Popular,
                'score' => $playerCount,
                'player_count' => $playerCount,
            ]);
        }

        $this->info('Stored ' . count($topGames) . ' popular games.');
    }

    /**
     * Get current popular games from real-time rich presence data.
     *
     * @return array<int, int>
     */
    private function getCurrentPopularGames(): array
    {
        $players = (new LoadThinActivePlayersListAction())->execute();

        $gameCounts = [];
        foreach ($players as $player) {
            $gameCounts[$player['game_id']] = ($gameCounts[$player['game_id']] ?? 0) + 1;
        }

        $gameIds = array_keys($gameCounts);
        $gamesWithAchievements = Game::whereIn('id', $gameIds)
            ->where('achievements_published', '>', 0)
            ->pluck('id')
            ->toArray();

        return array_filter(
            $gameCounts,
            fn ($count, $gameId) => in_array($gameId, $gamesWithAchievements),
            ARRAY_FILTER_USE_BOTH
        );
    }

    /**
     * Generate fake popular games for testing purposes during a historical backfill.
     */
    private function generateFakePopularGames(): void
    {
        $this->warn('Generating fake popular games for testing purposes only.');

        // Select 4 random games from the top 100 most popular games.
        $gameIds = Game::where('achievements_published', '>', 0)
            ->where('system_id', '!=', System::Events)
            ->where('title', 'NOT LIKE', '%[Subset%')
            ->orderByDesc('players_total')
            ->limit(100)
            ->get()
            ->shuffle()
            ->take(4)
            ->pluck('id');

        foreach ($gameIds as $gameId) {
            $fakePlayerCount = random_int(18, 60);

            GameActivitySnapshot::create([
                'game_id' => $gameId,
                'type' => GameActivitySnapshotType::Popular,
                'score' => $fakePlayerCount,
                'player_count' => $fakePlayerCount,
            ]);
        }

        $this->info('  Stored ' . $gameIds->count() . ' fake popular games.');
    }

    /**
     * Determine the trending reason based on this priority order:
     * 1. New set (2-14 days ago)
     * 2. Revision (0-14 days ago)
     * 3. Gaining traction (< 500 total players)
     * 4. Renewed interest (> 20,000 total players)
     * 5. Many more players (>= 10x trend)
     * 6. Default: More players than usual
     */
    private function determineTrendingReason(
        ?string $newSetDate,
        ?string $revisionDate,
        int $playersTotal,
        float $trend,
    ): TrendingReason {
        if ($newSetDate !== null) {
            return TrendingReason::NewSet;
        }

        if ($revisionDate !== null) {
            return TrendingReason::RevisedSet;
        }

        if ($playersTotal < 500) {
            return TrendingReason::GainingTraction;
        }

        if ($playersTotal > 20000) {
            return TrendingReason::RenewedInterest;
        }

        if ($trend >= 10) {
            return TrendingReason::ManyMorePlayers;
        }

        return TrendingReason::MorePlayers;
    }
}
