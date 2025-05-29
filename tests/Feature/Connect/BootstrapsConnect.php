<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Models\User;
use Illuminate\Support\Str;

trait BootstrapsConnect
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var User $user */
        $user = User::factory()->create(['appToken' => Str::random(16)]);
        $this->user = $user;
    }

    protected function apiParams(string $method, array $params = [], bool $credentials = true): array
    {
        // Laravel caches the authenticated user for the duration of the test.
        // Forcibly clear it out any time we generate new API params.
        auth()->forgetGuards();

        if ($credentials) {
            $params = array_merge(['u' => $this->user->User, 't' => $this->user->appToken], $params);
        }

        return array_merge(['r' => $method], $params);
    }

    protected function apiUrl(string $method, array $params = [], bool $credentials = true): string
    {
        $params = $this->apiParams($method, $params, $credentials);

        return sprintf('dorequest.php?%s', http_build_query($params));
    }
}
