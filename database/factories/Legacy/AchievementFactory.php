<?php

declare(strict_types=1);

namespace Database\Factories\Legacy;

use LegacyApp\Platform\Enums\AchievementPoints;
use LegacyApp\Platform\Enums\AchievementType;
use LegacyApp\Platform\Models\Achievement;
use LegacyApp\Support\Database\Eloquent\Factory;

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
            'Author' => $this->fakeUsername(),
            'Flags' => AchievementType::Unofficial,
            'Points' => array_rand(array_diff(AchievementPoints::cases(), [0])),
            'BadgeName' => '00001',
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'Flags' => AchievementType::OfficialCore,
        ]);
    }
}
