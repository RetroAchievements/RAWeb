<?php

declare(strict_types=1);

namespace Database\Factories\Legacy;

use Illuminate\Database\Eloquent\Factories\Factory;
use LegacyApp\Platform\Enums\AchievementType;
use LegacyApp\Platform\Models\Achievement;

/**
 * @extends Factory<Achievement>
 */
class AchievementFactory extends Factory
{
    protected $model = Achievement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'GameID' => 0,
            'Title' => ucwords($this->faker->sentence()),
            'Description' => ucwords($this->faker->sentence()),
            'MemAddr' => '0x000000',
            'Author' => 'Author',
            'Flags' => 0,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'Flags' => AchievementType::OfficialCore,
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'Flags' => AchievementType::Unofficial,
        ]);
    }
}
