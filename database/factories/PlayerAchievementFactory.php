<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Platform\Models\PlayerAchievement;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<PlayerAchievement>
 */
class PlayerAchievementFactory extends Factory
{
    protected $model = PlayerAchievement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'unlocked_at' => Carbon::now(),
        ];
    }

    public function hardcore(): static
    {
        return $this->state(fn (array $attributes) => [
            'unlocked_hardcore_at' => Carbon::now(),
        ]);
    }
}
