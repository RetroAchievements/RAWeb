<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DiscordRoleGrant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscordRoleGrant>
 */
class DiscordRoleGrantFactory extends Factory
{
    protected $model = DiscordRoleGrant::class;

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
