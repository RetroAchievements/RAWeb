<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaderboardEntry>
 */
class LeaderboardEntryFactory extends Factory
{
    protected $model = LeaderboardEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $leaderboard = Leaderboard::inRandomOrder()->first();
        $user = User::inRandomOrder()->first();

        return [
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user->id,
            'score' => rand(1, 1000),
        ];
    }
}
