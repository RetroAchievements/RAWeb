<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AchievementGroup;
use App\Models\AchievementSet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AchievementGroup>
 */
class AchievementGroupFactory extends Factory
{
    protected $model = AchievementGroup::class;

    public function definition(): array
    {
        return [
            'achievement_set_id' => AchievementSet::factory(),
            'label' => $this->faker->words(2, true),
            'order_column' => $this->faker->numberBetween(0, 10),
        ];
    }
}
