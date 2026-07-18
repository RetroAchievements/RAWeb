<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Game;
use App\Models\User;
use App\Models\UserGameBadgePreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserGameBadgePreference>
 */
class UserGameBadgePreferenceFactory extends Factory
{
    protected $model = UserGameBadgePreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'game_id' => Game::factory(),
            'sha1' => sha1(fake()->uuid()),
        ];
    }
}
