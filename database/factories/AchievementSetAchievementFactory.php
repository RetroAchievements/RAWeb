<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AchievementSetAchievement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AchievementSetAchievement>
 */
class AchievementSetAchievementFactory extends Factory
{
    protected $model = AchievementSetAchievement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'achievement_set_id' => 0,
            'achievement_id' => 0,
            'order_column' => 1,
        ];
    }
}
