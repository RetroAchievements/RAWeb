<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Leaderboard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Leaderboard>
 */
class LeaderboardFactory extends Factory
{
    protected $model = Leaderboard::class;

    public function definition(): array
    {
        $author = User::inRandomOrder()->first();

        return [
            'game_id' => 0,
            'title' => ucwords(fake()->words(2, true)),
            'description' => fake()->sentence(),
            'trigger_definition' => 'STA:0x000000=0::CAN:0x000001=2::SUB:0x000001=1::VAL:0x000002',
            'author_id' => $author?->id ?? 1,
            'format' => 'VALUE',
            'rank_asc' => false,
            'order_column' => rand(0, 500),
        ];
    }
}
