<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Achievement;
use App\Models\User;
use App\Platform\Enums\AchievementType;
use App\Support\Database\Eloquent\Concerns\FakesUsername;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Achievement>
 */
class AchievementFactory extends Factory
{
    use FakesUsername;

    protected $model = Achievement::class;

    public function definition(): array
    {
        $user = User::inRandomOrder()->first();

        // pick a random point value (prefer 5 and 10, exclude 0 and 100)
        $pointValues = [
            1, 1,
            2,
            3, 3,
            4,
            5, 5, 5, 5, 5, 5, 5, 5,
            10, 10, 10, 10,
            25, 25,
            50,
        ];

        return [
            'game_id' => 0,
            'title' => ucwords(fake()->words(2, true)),
            'description' => fake()->sentence(),
            'trigger_definition' => '0x000000',
            'user_id' => $user?->id ?? 1,
            'is_published' => false,
            'type' => null,
            'points' => fake()->randomElement($pointValues),
            'points_weighted' => rand(1, 1000),
            'image_name' => '00001',
            'modified_at' => Carbon::now(),
            'order_column' => rand(0, 500),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
        ]);
    }

    public function progression(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AchievementType::Progression,
        ]);
    }

    public function winCondition(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AchievementType::WinCondition,
        ]);
    }

    public function missable(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AchievementType::Missable,
        ]);
    }
}
