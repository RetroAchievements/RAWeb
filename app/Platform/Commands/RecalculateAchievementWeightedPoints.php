<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Community\Enums\RankType;
use App\Models\Game;
use App\Platform\Actions\CalculateAchievementWeightedPointsAction;
use App\Platform\Actions\UpdateGameAchievementsMetricsAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateAchievementWeightedPoints extends Command
{
    protected $signature = 'ra:platform:achievement:recalculate-weighted-points
                            {--game= : Process a single game by ID}';
    protected $description = 'Recalculate weighted points for all achievements using the updated formula';

    public function __construct(
        private readonly UpdateGameAchievementsMetricsAction $updateGameAchievementsMetrics,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $singleGameId = $this->option('game');
        if ($singleGameId) {
            $this->processSingleGame((int) $singleGameId);

            return;
        }

        $this->processAllAchievements();
    }

    private function processSingleGame(int $gameId): void
    {
        $game = Game::find($gameId);
        if (!$game) {
            $this->error("Game with ID {$gameId} not found.");

            return;
        }

        $this->info("Processing game [{$game->id}:{$game->title}]...");
        $this->updateGameAchievementsMetrics->execute($game);
        $this->info("Done. New points_weighted: {$game->fresh()->points_weighted}");
    }

    private function processAllAchievements(): void
    {
        $minPlayers = CalculateAchievementWeightedPointsAction::MIN_RANKED_PLAYERS;

        $rankedPlayerCount = countRankedUsers(RankType::TruePoints);
        if ($rankedPlayerCount < $minPlayers) {
            $this->warn("Ranked player count ({$rankedPlayerCount}) is below {$minPlayers} - using minimum for formula stability.");
            $rankedPlayerCount = $minPlayers;
        }

        $stablePlayerCount = $rankedPlayerCount * CalculateAchievementWeightedPointsAction::STABLE_PLAYER_RATIO;
        $maxRarity = $rankedPlayerCount * CalculateAchievementWeightedPointsAction::MAX_RARITY_RATIO;

        $this->info("Using ranked player count: {$rankedPlayerCount}");
        $this->info("Stable player threshold (1%): {$stablePlayerCount}");
        $this->info("Max rarity threshold (0.2%): {$maxRarity}");
        $this->newLine();

        // Step 1: update all achievements.points_weighted using the formula.
        $this->info("Updating all achievements.points_weighted...");
        $start = microtime(true);

        $weight = CalculateAchievementWeightedPointsAction::ADJUSTMENT_WEIGHT;
        $baseWeight = 1 - $weight;

        $achievementsUpdated = DB::update(<<<SQL
            UPDATE achievements a
            JOIN games g ON g.id = a.game_id
            SET a.points_weighted = CAST(
                a.points * {$baseWeight} + a.points * {$weight} * (
                    CASE
                        WHEN (GREATEST(g.players_hardcore, 1) / GREATEST(a.unlocks_hardcore, 1)) > ?
                        THEN ? * (1 + LOG10((GREATEST(g.players_hardcore, 1) / GREATEST(a.unlocks_hardcore, 1)) / ?))
                        ELSE (GREATEST(g.players_hardcore, 1) / GREATEST(a.unlocks_hardcore, 1))
                    END
                ) * (
                    GREATEST(1.0, 1.0 / LOG10(1 + GREATEST(g.players_hardcore, 1) / (? / 9)))
                )
                AS UNSIGNED
            ),
            a.updated_at = NOW()
            WHERE a.is_promoted = 1
        SQL, [$maxRarity, $maxRarity, $maxRarity, $stablePlayerCount]);

        $this->info("Updated {$achievementsUpdated} achievements in " . round(microtime(true) - $start, 2) . "s");

        // Step 2: update all games.points_weighted from their achievements.
        $this->info("Updating all games.points_weighted...");
        $start = microtime(true);

        $gamesUpdated = DB::update(<<<SQL
            UPDATE games g
            SET g.points_weighted = (
                SELECT COALESCE(SUM(a.points_weighted), 0)
                FROM achievements a
                WHERE a.game_id = g.id AND a.is_promoted = 1
            )
            WHERE EXISTS (SELECT 1 FROM achievements a WHERE a.game_id = g.id AND a.is_promoted = 1)
        SQL);

        $this->info("Updated {$gamesUpdated} games in " . round(microtime(true) - $start, 2) . "s");

        // Step 3: update all achievement_sets.points_weighted from their achievements.
        $this->info("Updating achievement_sets.points_weighted...");
        $start = microtime(true);

        $setsUpdated = DB::update(<<<SQL
            UPDATE achievement_sets ach_set
            JOIN (
                SELECT asa.achievement_set_id, COALESCE(SUM(a.points_weighted), 0) as total_points_weighted
                FROM achievement_set_achievements asa
                INNER JOIN achievements a ON a.id = asa.achievement_id
                WHERE a.is_promoted = 1
                GROUP BY asa.achievement_set_id
            ) calc ON calc.achievement_set_id = ach_set.id
            SET ach_set.points_weighted = calc.total_points_weighted
        SQL);

        $this->info("Updated {$setsUpdated} achievement sets in " . round(microtime(true) - $start, 2) . "s");

        $this->newLine();
        $this->info("Done!");
    }
}
