<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\GameAchievementSet;
use App\Platform\Enums\GameAchievementSetType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameAchievementSet>
 */
class GameAchievementSetFactory extends Factory
{
    protected $model = GameAchievementSet::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => null,
            'type' => GameAchievementSetType::Core,
        ];
    }
}
