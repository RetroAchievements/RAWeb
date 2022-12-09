<?php

declare(strict_types=1);

namespace Database\Factories\Legacy;

use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use LegacyApp\Site\Models\User;

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
        $username = mb_substr(str_replace('.', '', $this->faker->unique()->userName), 0, 20);

        return [
            'User' => $username,
            'EmailAddress' => $this->faker->unique()->safeEmail,
            'Password' => Hash::make('password'),
            'SaltedPass' => '',
            'Permissions' => 1,
            'RAPoints' => random_int(0, 10000),
            'fbUser' => 0,
            'Untracked' => 0,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'Permissions' => 0,
        ]);
    }
}
