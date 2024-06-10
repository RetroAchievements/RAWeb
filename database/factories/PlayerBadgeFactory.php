<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Community\Enums\AwardType;
use App\Models\PlayerBadge;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlayerBadge>
 */
class PlayerBadgeFactory extends Factory
{
    protected $model = PlayerBadge::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::inRandomOrder()->first();

        return [
            'user_id' => $user?->id ?? 1,
            'AwardType' => AwardType::Mastery,
            'AwardData' => fake()->numberBetween(0, 9999) * 10,
            'AwardDataExtra' => 0,
            'DisplayOrder' => 0,
        ];
    }
}
