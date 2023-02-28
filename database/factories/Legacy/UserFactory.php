<?php

declare(strict_types=1);

namespace Database\Factories\Legacy;

use Exception;
use Illuminate\Support\Facades\Hash;
use LegacyApp\Site\Enums\Permissions;
use LegacyApp\Site\Models\User;
use LegacyApp\Support\Database\Eloquent\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * @return array<string, mixed>
     * @throws Exception
     */
    public function definition(): array
    {
        return [
            // required
            'User' => $this->fakeUsername(),
            'EmailAddress' => $this->faker->unique()->safeEmail,
            'Password' => Hash::make('password'),
            'SaltedPass' => '',
            'Permissions' => Permissions::Registered,
            'RAPoints' => random_int(0, 10000),
            'fbUser' => 0,
            'Untracked' => 0,
            'UserWallActive' => 1,

            // nullable
            'APIKey' => 'apiKey',
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'Permissions' => Permissions::Unregistered,
        ]);
    }

    public function untracked(): static
    {
        return $this->state(fn (array $attributes) => [
            'Untracked' => 1,
        ]);
    }
}
