<?php

declare(strict_types=1);

namespace Tests\Feature\Connect;

use App\Site\Models\User;
use Illuminate\Support\Str;

trait BootstrapsConnect
{
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        config(['feature.aggregate_queries' => true]);

        /** @var User $user */
        $user = User::factory()->create(['appToken' => Str::random(16)]);
        $this->user = $user;
    }

    protected function apiParams(string $method, array $params = [], bool $credentials = true): array
    {
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
