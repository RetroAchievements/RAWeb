<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Site\Enums\Permissions;
use App\Site\Models\User;
use App\Support\Database\Eloquent\Concerns\FakesUsername;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    use FakesUsername;

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
            'EmailAddress' => fake()->unique()->safeEmail,
            'Password' => Hash::make('password'),
            'SaltedPass' => '',
            'Permissions' => Permissions::Registered,
            'RAPoints' => fake()->numberBetween(0, 9999) * 10,
            'RASoftcorePoints' => 0,
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
            // 'email_verified_at' => null,
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
