<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AchievementFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => ucwords($this->faker->words(2, true)),
            // 'status_flag' => mt_rand(1, 3),
            'description' => $this->faker->sentence(),
            // 'badge_name' => 'badge',
        ];
    }
}
