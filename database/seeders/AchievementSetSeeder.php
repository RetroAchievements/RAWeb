<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AchievementSet;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\GameHash;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Database\Seeder;

class AchievementSetSeeder extends Seeder
{
    public function run(): void
    {
        $games = Game::withCount('hashes')
            ->having('hashes_count', '>=', 6)
            ->limit(10)
            ->get();

        foreach ($games as $game) {
            // Always create a core achievement set for each game.
            $coreAchievementSet = AchievementSet::factory()->create();
            GameAchievementSet::factory()->type(AchievementSetType::Core->value)->create([
                'game_id' => $game->id,
                'achievement_set_id' => $coreAchievementSet->id,
            ]);

            // Randomly decide the set configuration for the game.
            // 1 = Only Core, 2 = Core + Bonus, 3 = Core + Specialty, 4 = All types.
            $setConfig = mt_rand(1, 4);

            $allGameHashIds = GameHash::where('game_id', $game->id)->pluck('id')->toArray();

            // If a specialty set is needed, we must reserve a unique hash.
            $reservedSpecialtyHashIds = ($setConfig === 3 || $setConfig === 4)
                ? GameHash::where('game_id', $game->id)->inRandomOrder()->take(1)->pluck('id')->toArray()
                : [];

            // Associate the core set with all of the game's hashes.
            foreach ($allGameHashIds as $gameHashId) {
                $coreAchievementSet->gameHashes()->attach($gameHashId, ['compatible' => true]);
            }

            // Create bonus sets based on the random set configuration.
            if ($setConfig === 2 || $setConfig === 4) {
                // It's safe to assume most of the time the bonus set(s) will be
                // compatible with the same hashes the core set is compatible with.
                $this->createBonusSets($game, $allGameHashIds);
            }

            // Create specialty sets based on the random set configuration.
            if ($setConfig === 3 || $setConfig === 4) {
                // The specialty set will likely only be compatible with unique hashes.
                $this->createSpecialtySets($game, $reservedSpecialtyHashIds);
            }
        }
    }

    private function createBonusSets(Game $game, array $gameHashIds): void
    {
        $numBonusSets = mt_rand(1, 3);
        for ($i = 0; $i < $numBonusSets; $i++) {
            $bonusAchievementSet = AchievementSet::factory()->create();
            GameAchievementSet::factory()->type(AchievementSetType::Bonus->value)->create([
                'game_id' => $game->id,
                'achievement_set_id' => $bonusAchievementSet->id,
            ]);

            foreach ($gameHashIds as $gameHashId) {
                $bonusAchievementSet->gameHashes()->attach($gameHashId, ['compatible' => true]);
            }
        }
    }

    private function createSpecialtySets(Game $game, array $reservedHashIds): void
    {
        foreach ($reservedHashIds as $reservedHashId) {
            $specialtyAchievementSet = AchievementSet::factory()->create();
            GameAchievementSet::factory()->type(AchievementSetType::Specialty->value)->create([
                'game_id' => $game->id,
                'achievement_set_id' => $specialtyAchievementSet->id,
            ]);

            // Attach the reserved hash to the specialty set.
            $specialtyAchievementSet->gameHashes()->attach($reservedHashId, ['compatible' => true]);
        }
    }
}
