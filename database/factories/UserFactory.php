<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Site\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $username = mb_substr(str_replace('.', '', $this->faker->unique()->userName), 0, 20);

        return [
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'points_total' => $this->faker->numberBetween(0, 9999) * 10,
            'remember_token' => Str::random(10),
            'username' => $username,
            'display_name' => $username,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
