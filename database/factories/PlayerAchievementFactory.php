<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Platform\Enums\UnlockMode;
use App\Platform\Models\PlayerAchievement;
use App\Support\Database\Eloquent\Concerns\FakesUsername;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

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
