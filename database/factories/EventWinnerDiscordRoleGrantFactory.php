<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\EventWinnerDiscordRoleGrant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventWinnerDiscordRoleGrant>
 */
class EventWinnerDiscordRoleGrantFactory extends Factory
{
    protected $model = EventWinnerDiscordRoleGrant::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'discord_role_id' => fake()->numerify('##################'),
            'discord_user_id' => fake()->unique()->numerify('##################'),
            'expires_at' => now()->addWeek(),
        ];
    }
}
