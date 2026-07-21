<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PlayerGlobalRanking;
use App\Models\User;
use App\Platform\Enums\GlobalRankingMode;
use App\Platform\Enums\GlobalRankingWindow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlayerGlobalRanking>
 */
class PlayerGlobalRankingFactory extends Factory
{
    protected $model = PlayerGlobalRanking::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'window' => GlobalRankingWindow::AllTime,
            'mode' => GlobalRankingMode::Hardcore,
            'achievements_unlocked' => fake()->numberBetween(1, 100),
            'points' => fake()->numberBetween(250, 100_000),
            'points_weighted' => fake()->numberBetween(1_250, 100_000),
            'awards_count' => 0,
            'rank_number' => fake()->numberBetween(1, 1_000),
            'weighted_rank_number' => fake()->numberBetween(1, 1_000),
        ];
    }
}
