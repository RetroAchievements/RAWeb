<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OAuthScope;
use App\Models\OAuthClient;
use App\Models\OAuthGrant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OAuthGrant>
 */
class OAuthGrantFactory extends Factory
{
    protected $model = OAuthGrant::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'client_id' => OAuthClient::factory(),
            'scopes' => [OAuthScope::Read->value],
            'first_granted_at' => now(),
            'revoked_at' => null,
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn () => ['revoked_at' => now()]);
    }
}
