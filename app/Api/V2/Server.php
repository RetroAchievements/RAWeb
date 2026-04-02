<?php

declare(strict_types=1);

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

        // Register custom request classes for writable resources
        RequestResolver::register(RequestResolver::REQUEST, 'message-threads', MessageThreads\MessageThreadRequest::class);
        RequestResolver::register(RequestResolver::REQUEST, 'messages', Messages\MessageRequest::class);
        RequestResolver::register(RequestResolver::REQUEST, 'game-invites', GameInvites\GameInviteRequest::class);
        RequestResolver::register(RequestResolver::REQUEST, 'looking-for-group-posts', LookingForGroupPosts\LookingForGroupPostRequest::class);
        RequestResolver::register(RequestResolver::REQUEST, 'looking-for-group-invites', LookingForGroupInvites\LookingForGroupInviteRequest::class);

        // Register custom collection query classes
        RequestResolver::register(RequestResolver::COLLECTION_QUERY, 'message-threads', MessageThreads\MessageThreadCollectionQuery::class);
        RequestResolver::register(RequestResolver::COLLECTION_QUERY, 'game-invites', GameInvites\GameInviteCollectionQuery::class);
        RequestResolver::register(RequestResolver::COLLECTION_QUERY, 'looking-for-group-posts', LookingForGroupPosts\LookingForGroupPostCollectionQuery::class);
        RequestResolver::register(RequestResolver::COLLECTION_QUERY, 'looking-for-group-invites', LookingForGroupInvites\LookingForGroupInviteCollectionQuery::class);
    }

    /**
     * Get the server's list of schemas.
     */
    protected function allSchemas(): array
    {
        return [
            Achievements\AchievementSchema::class,
            AchievementSets\AchievementSetSchema::class,
            GameHashes\GameHashSchema::class,
            Games\GameSchema::class,
            GameInvites\GameInviteSchema::class,
            Hubs\HubSchema::class,
            LeaderboardEntries\LeaderboardEntrySchema::class,
            Leaderboards\LeaderboardSchema::class,
            LookingForGroupPosts\LookingForGroupPostSchema::class,
            LookingForGroupInvites\LookingForGroupInviteSchema::class,
            MessageThreads\MessageThreadSchema::class,
            Messages\MessageSchema::class,
            PlayerAchievements\PlayerAchievementSchema::class,
            PlayerAchievementSets\PlayerAchievementSetSchema::class,
            PlayerGames\PlayerGameSchema::class,
            Systems\SystemSchema::class,
            Users\UserSchema::class,
        ];
    }
}
