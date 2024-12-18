<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\UserGameListEntry;
use App\Models\User;
use App\Models\Game;
use Illuminate\Database\Eloquent\Factories\Factory;

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
