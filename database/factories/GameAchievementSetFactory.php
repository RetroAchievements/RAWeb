<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AchievementSet;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameAchievementSet>
 */
class GameAchievementSetFactory extends Factory
{
    protected $model = GameAchievementSet::class;

    public function definition(): array
    {
        return [
            'game_id' => Game::factory(),
            'achievement_set_id' => AchievementSet::factory(),
            'type' => AchievementSetType::Core,
            'title' => null,
            'order_column' => 0,
        ];
    }

    public function type(string $type): self
    {
        return $this->state(function (array $attributes) use ($type) {
            return [
                'type' => $type,
                'title' => $type === AchievementSetType::Core->value ? null : $this->faker->words(3, true),
                'order_column' => $type === AchievementSetType::Core->value ? 0 : $this->faker->numberBetween(1, 10),
            ];
        });
    }
}
