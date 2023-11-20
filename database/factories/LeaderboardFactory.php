<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Platform\Models\Leaderboard;
use App\Support\Database\Eloquent\Concerns\FakesUsername;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Leaderboard>
 */
class LeaderboardFactory extends Factory
{
    use FakesUsername;

    protected $model = Leaderboard::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'GameID' => 0,
            'Title' => ucwords(fake()->words(2, true)),
            'Description' => fake()->sentence(),
            'Mem' => 'STA:0x000000=0::CAN:0x000001=2::SUB:0x000001=1::VAL:0x000002',
            'Author' => $this->fakeUsername(),
            'Format' => 'VALUE',
            'LowerIsBetter' => 0,
            'DisplayOrder' => rand(0, 500),
        ];
    }
}
