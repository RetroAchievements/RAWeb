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

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $author = User::inRandomOrder()->first();

        return [
            'GameID' => 0,
            'Title' => ucwords(fake()->words(2, true)),
            'Description' => fake()->sentence(),
            'Mem' => 'STA:0x000000=0::CAN:0x000001=2::SUB:0x000001=1::VAL:0x000002',
            'Author' => $author->User,
            'author_id' => $author->id,
            'Format' => 'VALUE',
            'LowerIsBetter' => 0,
            'DisplayOrder' => rand(0, 500),
        ];
    }
}
