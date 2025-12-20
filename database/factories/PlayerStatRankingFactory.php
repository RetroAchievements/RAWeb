<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\PlayerStatRanking;
use App\Models\User;
use App\Platform\Enums\PlayerStatRankingKind;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlayerStatRanking>
 */
class PlayerStatRankingFactory extends Factory
{
    protected $model = PlayerStatRanking::class;

    public function definition(): array
    {
        $user = User::inRandomOrder()->first();

        return [
            'user_id' => $user->id,
            'system_id' => null,
            'kind' => PlayerStatRankingKind::RetailBeaten,
            'total' => rand(1, 100),
            'rank_number' => rand(1, 1000),
            'row_number' => rand(1, 1000),
        ];
    }
}
