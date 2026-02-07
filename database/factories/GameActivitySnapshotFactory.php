<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Community\Enums\GameActivitySnapshotType;
use App\Community\Enums\TrendingReason;
use App\Models\Game;
use App\Models\GameActivitySnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameActivitySnapshot>
 */
class GameActivitySnapshotFactory extends Factory
{
    protected $model = GameActivitySnapshot::class;

    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'type' => GameActivitySnapshotType::Trending,
            'score' => $this->faker->randomFloat(2, 1, 100),
            'player_count' => null,
            'trend_multiplier' => $this->faker->randomFloat(2, 1.5, 20),
            'trending_reason' => TrendingReason::MorePlayers->value,
        ];
    }

    public function trending(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => GameActivitySnapshotType::Trending,
        ]);
    }

    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => GameActivitySnapshotType::Popular,
            'trend_multiplier' => null,
            'trending_reason' => null,
            'player_count' => $this->faker->numberBetween(1, 500),
        ]);
    }
}
