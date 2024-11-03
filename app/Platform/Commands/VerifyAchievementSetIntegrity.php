<?php

declare(strict_types=1);

namespace App\Platform\Commands;

use App\Models\AchievementSet;
use App\Models\Game;
use Exception;
use Illuminate\Console\Command;

class VerifyAchievementSetIntegrity extends Command
{
    protected $signature = "ra:platform:verify-achievement-set-integrity
                            {gameId? : Target a single game}";
    protected $description = "Make sure game achievement sets are correctly aligned with legacy game flags";

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $gameId = $this->argument('gameId');

        if ($gameId !== null) {
            $this->verifyGame(Game::findOrFail($gameId));
        } else {
            $this->verifyAllGames();
        }
    }

    private function verifyGame(Game $game): void
    {
        // Get the core achievement set.
        $gameCoreSet = $game->gameAchievementSets()->core()->first();
        if (!$gameCoreSet) {
            $this->error("No core achievement set found for game {$game->ID}");

            return;
        }

        $set = $gameCoreSet->achievementSet()->first();
        if (!$set) {
            $this->error("Achievement set not found for game achievement set {$gameCoreSet->id}");

            return;
        }

        $errors = $this->checkGameMetrics($game, $set);
        $achievementErrors = $this->checkAchievements($game, $set);
        $errors = array_merge($errors, $achievementErrors);

        if (!empty($errors)) {
            $this->error("Found " . count($errors) . " integrity issues for [{$game->id}:{$game->title}]:");
            foreach ($errors as $error) {
                $this->line("  - {$error}");
            }
        }
    }

    private function verifyAllGames(): void
    {
        $games = Game::whereNotNull('achievement_set_version_hash')->cursor();
        $gamesWithErrors = 0;
        $totalGames = 0;

        foreach ($games as $game) {
            $totalGames++;

            try {
                $this->verifyGame($game);
            } catch (Exception $e) {
                $this->error("Error processing game {$game->ID}: " . $e->getMessage());
                $gamesWithErrors++;
            }
        }

        $this->info("\nVerification complete:");
        $this->line("Total games processed: {$totalGames}");
        $this->line("Games with errors: {$gamesWithErrors}");
    }

    private function checkGameMetrics(Game $game, AchievementSet $set): array
    {
        $errors = [];

        // Basic metrics comparison
        $comparisons = [
            ['field' => 'achievements_published', 'game' => $game->achievements()->published()->count(), 'set' => $set->achievements_published],
            ['field' => 'achievements_unpublished', 'game' => $game->achievements()->unpublished()->count(), 'set' => $set->achievements_unpublished],
            ['field' => 'players_total', 'game' => $game->players_total, 'set' => $set->players_total],
            ['field' => 'players_hardcore', 'game' => $game->players_hardcore, 'set' => $set->players_hardcore],
            ['field' => 'points_total', 'game' => $game->points_total, 'set' => $set->points_total],
            ['field' => 'points_weighted', 'game' => $game->TotalTruePoints, 'set' => $set->points_weighted],
        ];

        foreach ($comparisons as $comparison) {
            if ($comparison['game'] !== $comparison['set']) {
                $errors[] = sprintf(
                    "%s mismatch - Game: %s, Set: %s",
                    str_replace('_', ' ', ucfirst($comparison['field'])),
                    $comparison['game'] ?? 'null',
                    $comparison['set'] ?? 'null'
                );
            }
        }

        return $errors;
    }

    private function checkAchievements(Game $game, AchievementSet $set): array
    {
        $errors = [];

        // Get all achievements from both sources.
        $gameAchievements = $game->achievements()
            ->whereNull('Achievements.deleted_at')
            ->get()
            ->keyBy('ID');

        $setAchievements = $set->achievements()
            ->whereNull('Achievements.deleted_at')
            ->get()
            ->keyBy('ID');

        // Check for missing achievements in either direction.
        $missingInSet = $gameAchievements->diffKeys($setAchievements);
        $missingInGame = $setAchievements->diffKeys($gameAchievements);

        if ($missingInSet->isNotEmpty()) {
            $errors[] = sprintf(
                "Achievements missing from set: %s",
                $missingInSet->pluck('Title')->join(', ')
            );
        }

        if ($missingInGame->isNotEmpty()) {
            $errors[] = sprintf(
                "Achievements missing from game: %s",
                $missingInGame->pluck('Title')->join(', ')
            );
        }

        // Get ordering from game achievements.
        $gameOrder = $game->achievements()
            ->whereNull('Achievements.deleted_at')
            ->orderBy('DisplayOrder')
            ->pluck('Achievements.ID')
            ->toArray();

        // Get ordering from set achievements using the pivot table's order_column.
        $setOrder = $set->achievements()
            ->whereNull('Achievements.deleted_at')
            ->orderBy('achievement_set_achievements.order_column')
            ->pluck('Achievements.ID')
            ->toArray();

        if ($gameOrder !== $setOrder) {
            $this->line("Game order: " . implode(', ', $gameOrder));
            $this->line("Set order: " . implode(', ', $setOrder));
            $errors[] = "Achievement ordering mismatch between game and set";
        }

        return $errors;
    }
}
