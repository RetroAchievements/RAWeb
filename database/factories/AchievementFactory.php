<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Achievement;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementPoints;
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

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::inRandomOrder()->first();

        return [
            'GameID' => 0,
            'Title' => ucwords(fake()->words(2, true)),
            'Description' => fake()->sentence(),
            'MemAddr' => '0x000000',
            'Author' => $user?->username ?? $this->fakeUsername(),
            'user_id' => $user?->id ?? 1,
            'Flags' => AchievementFlag::Unofficial,
            'type' => null,
            'Points' => array_rand(array_diff(AchievementPoints::cases(), [0])),
            'TrueRatio' => rand(1, 1000),
            'BadgeName' => '00001',
            'DateModified' => Carbon::now(),
            'DisplayOrder' => rand(0, 500),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'Flags' => AchievementFlag::OfficialCore,
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
