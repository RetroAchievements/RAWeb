<?php

namespace Database\Factories\Legacy;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use LegacyApp\Platform\Enums\UnlockMode;
use LegacyApp\Platform\Models\PlayerAchievement;
use LegacyApp\Support\Database\Eloquent\FakesUsername;

/**
 * @extends Factory<PlayerAchievement>
 */
class PlayerAchievementFactory extends Factory
{
    use FakesUsername;

    protected $model = PlayerAchievement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'User' => $this->fakeUsername(),
            'HardcoreMode' => UnlockMode::Softcore,
            'Date' => Carbon::now(),
        ];
    }

    public function hardcore(): static
    {
        return $this->state(fn (array $attributes) => [
            'HardcoreMode' => UnlockMode::Hardcore,
        ]);
    }
}
