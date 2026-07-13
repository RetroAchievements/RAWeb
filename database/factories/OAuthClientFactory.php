<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OAuthClient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OAuthClient>
 */
class OAuthClientFactory extends Factory
{
    protected $model = OAuthClient::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'owner_type' => (new User())->getMorphClass(),
            'owner_id' => User::factory(),
            'name' => $this->faker->company(),
            'secret' => Str::random(40),
            'provider' => null,
            'redirect_uris' => ['https://example.com/oauth/callback'],
            'grant_types' => ['authorization_code', 'refresh_token'],
            'revoked' => false,
        ];
    }

    public function public(): static
    {
        return $this->state(fn () => ['secret' => null]);
    }

    public function revoked(): static
    {
        return $this->state(fn () => ['revoked' => true]);
    }
}
