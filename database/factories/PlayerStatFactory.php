<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PlayerStat;
use App\Models\User;
use App\Platform\Enums\PlayerStatType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlayerStat>
 */
class PlayerStatFactory extends Factory
{
    protected $model = PlayerStat::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::inRandomOrder()->first();

        return [
            'user_id' => $user->id,
            'type' => PlayerStatType::GamesBeatenHardcoreRetail,
            'value' => rand(1, 100),
        ];
    }
}
