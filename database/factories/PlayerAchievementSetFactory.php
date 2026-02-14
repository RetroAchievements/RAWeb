<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AchievementSet;
use App\Models\PlayerAchievementSet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlayerAchievementSet>
 */
class PlayerAchievementSetFactory extends Factory
{
    protected $model = PlayerAchievementSet::class;

    public function definition(): array
    {
        $user = User::inRandomOrder()->first();
        $achievementSet = AchievementSet::inRandomOrder()->first();

        return [
            'user_id' => $user?->id ?? 1,
            'achievement_set_id' => $achievementSet?->id ?? 1,
        ];
    }
}
