<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlayerGame>
 */
class PlayerGameFactory extends Factory
{
    protected $model = PlayerGame::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::inRandomOrder()->first();
        $game = Game::inRandomOrder()->first();

        return [
            'user_id' => $user?->id ?? 1,
            'game_id' => $game?->id ?? 1,
        ];
    }
}
