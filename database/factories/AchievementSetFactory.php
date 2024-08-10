<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AchievementSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AchievementSet>
 */
class AchievementSetFactory extends Factory
{
    protected $model = AchievementSet::class;

    public function definition(): array
    {
        return [
            'players_total' => $this->faker->numberBetween(0, 1000),
            'players_hardcore' => $this->faker->numberBetween(0, 500),
            'achievements_published' => $this->faker->numberBetween(0, 100),
            'achievements_unpublished' => $this->faker->numberBetween(0, 50),
            'points_total' => $this->faker->numberBetween(0, 800),
            'points_weighted' => $this->faker->numberBetween(0, 1200),
        ];
    }
}
