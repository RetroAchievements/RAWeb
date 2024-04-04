<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Permissions;
use App\Models\User;
use App\Support\Database\Eloquent\Concerns\FakesUsername;
use Exception;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

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
            'email_verified_at' => now(),
            'Permissions' => Permissions::Registered,
            'Password' => Hash::make('password'),
            'SaltedPass' => '',
            'RAPoints' => fake()->numberBetween(0, 9999) * 10,
            'RASoftcorePoints' => 0,
            'ContribCount' => 0,
            'TrueRAPoints' => 0,
            'DeleteRequested' => null,
            'fbUser' => 0,
            'Untracked' => 0,
            'UserWallActive' => 1,
            'muted_until' => null,

            // nullable
            'APIKey' => 'apiKey',
            'remember_token' => Str::random(10),
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
