<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameRecentPlayer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameRecentPlayer>
 */
class GameRecentPlayerFactory extends Factory
{
    protected $model = GameRecentPlayer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'user_id' => User::factory(),
            'rich_presence' => $this->faker->sentence(),
            'rich_presence_updated_at' => now(),
        ];
    }
}
