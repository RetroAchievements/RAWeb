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

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'players_total' => rand(1, 10000),
            'players_hardcore' => rand(1, 10000),
            'achievements_published' => rand(1, 100),
            'achievements_unpublished' => rand(1, 100),
            'points_total' => rand(1, 400),
            'points_weighted' => rand(1, 2000),
            'deleted_at' => null,
        ];
    }
}
