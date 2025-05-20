<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Game;
use App\Models\GameTitle;
use App\Platform\Enums\GameReleaseRegion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameTitle>
 */
class GameTitleFactory extends Factory
{
    protected $model = GameTitle::class;

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
            'is_canonical' => false,
        ];
    }

    public function canonical(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'is_canonical' => true,
            ];
        });
    }
}
