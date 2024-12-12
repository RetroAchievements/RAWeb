<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameSet;
use App\Models\GameSetGame;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameSetGame>
 */
class GameSetGameFactory extends Factory
{
    protected $model = GameSetGame::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_set_id' => GameSet::factory(),
            'game_id' => Game::factory(),
        ];
    }
}
