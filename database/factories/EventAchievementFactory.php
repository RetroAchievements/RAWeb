<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Achievement;
use App\Models\EventAchievement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventAchievement>
 */
class EventAchievementFactory extends Factory
{
    protected $model = EventAchievement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'achievement_id' => Achievement::factory(),
            'source_achievement_id' => Achievement::factory(),
            'active_from' => now(),
            'active_until' => now()->addWeek(),
        ];
    }
}
