<?php

declare(strict_types=1);

namespace Database\Factories\Legacy;

use Illuminate\Database\Eloquent\Factories\Factory;
use LegacyApp\Platform\Enums\AchievementPoints;
use LegacyApp\Platform\Enums\AchievementType;
use LegacyApp\Platform\Models\Achievement;
use LegacyApp\Support\Database\Eloquent\FakesUsername;

/**
 * @extends Factory<Achievement>
 */
class AchievementFactory extends Factory
{
    use FakesUsername;

    protected $model = Achievement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'GameID' => 0,
            'Title' => ucwords(fake()->sentence()),
            'Description' => ucwords(fake()->sentence()),
            'MemAddr' => '0x000000',
            'Author' => $this->fakeUsername(),
            'Flags' => AchievementType::Unofficial,
            'Points' => array_rand(AchievementPoints::cases()),
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
