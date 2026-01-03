<?php

namespace App\Api\V2;

use LaravelJsonApi\Core\Server\Server as BaseServer;
use LaravelJsonApi\Laravel\Http\Requests\RequestResolver;

class Server extends BaseServer
{
    /**
     * The base URI namespace for this server.
     */
    protected string $baseUri = 'api/v2';

    /**
     * Bootstrap the server when it is handling an HTTP request.
     */
    public function serving(): void
    {
        RequestResolver::useDefault(
            RequestResolver::COLLECTION_QUERY,
            DefaultCollectionQuery::class
        );
    }

    /**
     * Get the server's list of schemas.
     */
    protected function allSchemas(): array
    {
        return [
            Achievements\AchievementSchema::class,
            AchievementSets\AchievementSetSchema::class,
            Games\GameSchema::class,
            Systems\SystemSchema::class,
            Users\UserSchema::class,
        ];
    }
}
