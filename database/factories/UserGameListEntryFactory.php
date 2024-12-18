<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Game;
use App\Models\User;
use App\Models\UserGameListEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserGameListEntry>
 */
class UserGameListEntryFactory extends Factory
{
    protected $model = UserGameListEntry::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => '',
            'GameID' => Game::factory(),
        ];
    }
}
