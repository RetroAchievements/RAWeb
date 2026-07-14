<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Community\Enums\UserGameListType;
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
            'type' => 'achievement_set_request',
            'game_id' => Game::factory(),
        ];
    }

    public function play(): static
    {
        return $this->state(fn () => ['type' => UserGameListType::Play]);
    }

    public function setRequest(): static
    {
        return $this->state(fn () => ['type' => UserGameListType::AchievementSetRequest]);
    }

    public function develop(): static
    {
        return $this->state(fn () => ['type' => UserGameListType::Develop]);
    }
}
