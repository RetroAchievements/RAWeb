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

    public function definition(): array
    {
        $user = User::inRandomOrder()->first();

        return [
            'user_id' => $user?->id ?? 1,
            'award_type' => AwardType::Mastery,
            'award_data' => fake()->numberBetween(0, 9999) * 10,
            'award_data_extra' => 0,
            'order_column' => 0,
        ];
    }
}
