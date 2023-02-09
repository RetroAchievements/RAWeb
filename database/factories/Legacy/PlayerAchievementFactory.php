<?php

declare(strict_types=1);

namespace Database\Factories\Legacy;

use LegacyApp\Platform\Enums\UnlockMode;
use LegacyApp\Platform\Models\PlayerAchievement;
use LegacyApp\Support\Database\Eloquent\Factory;

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
            'User' => $this->fakeUsername(),
            'HardcoreMode' => UnlockMode::Softcore,
        ];
    }

    public function hardcore(): static
    {
        return $this->state(fn (array $attributes) => [
            'HardcoreMode' => UnlockMode::Hardcore,
        ]);
    }
}
