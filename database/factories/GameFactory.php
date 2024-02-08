<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    protected $model = Game::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Title' => ucwords(fake()->words(2, true)),
            'ConsoleID' => 0,
            'ImageIcon' => '/Images/000001.png',
        ];
    }
}
