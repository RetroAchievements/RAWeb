<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameRelease;
use App\Platform\Enums\GameReleaseRegion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameRelease>
 */
class GameReleaseFactory extends Factory
{
    protected $model = GameRelease::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $game = Game::inRandomOrder()->first();
        $regions = [
            GameReleaseRegion::NorthAmerica,
            GameReleaseRegion::Japan,
            GameReleaseRegion::Europe,
            GameReleaseRegion::Australia,
            GameReleaseRegion::Korea,
            GameReleaseRegion::China,
            GameReleaseRegion::Worldwide,
        ];

        return [
            'game_id' => $game->id,
            'title' => $this->faker->words(3, true),
            'region' => $this->faker->randomElement($regions),
            'is_canonical_game_title' => false,
        ];
    }
}
