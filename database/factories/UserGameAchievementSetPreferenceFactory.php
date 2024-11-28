<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GameAchievementSet;
use App\Models\User;
use App\Models\UserGameAchievementSetPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserGameAchievementSetPreference>
 */
class UserGameAchievementSetPreferenceFactory extends Factory
{
    protected $model = UserGameAchievementSetPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'game_achievement_set_id' => GameAchievementSet::factory(),
            'opted_in' => true,
        ];
    }
}
