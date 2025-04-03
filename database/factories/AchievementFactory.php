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

        // pick a random point value (prefer 5 and 10, exclude 0)
        $points = match (random_int(0, 6)) {
            0 => 5,
            1 => 5,
            2 => 5,
            3 => 5,
            4 => 10,
            5 => 10,
            default => AchievementPoints::cases()[array_rand(AchievementPoints::cases())],
        };
        if ($points === 0) {
            $points = 5;
        }

        return [
            'GameID' => 0,
            'Title' => ucwords(fake()->words(2, true)),
            'Description' => fake()->sentence(),
            'MemAddr' => '0x000000',
            'user_id' => $user?->id ?? 1,
            'Flags' => AchievementFlag::Unofficial->value,
            'type' => null,
            'Points' => $points,
            'TrueRatio' => rand(1, 1000),
            'BadgeName' => '00001',
            'DateModified' => Carbon::now(),
            'DisplayOrder' => rand(0, 500),
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'Flags' => AchievementFlag::OfficialCore->value,
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
