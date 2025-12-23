<?php

declare(strict_types=1);

namespace App\Api\V2\Users;

use App\Models\User;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Link;
use LaravelJsonApi\Core\Document\Links;
use LaravelJsonApi\Core\Resources\JsonApiResource;

/**
 * @property User $resource
 */
class UserResource extends JsonApiResource
{
    /**
     * Use ULID as the JSON:API resource ID instead of numeric ID.
     * This prevents enumeration attacks and aligns with permalink strategy.
     */
    public function id(): string
    {
        return (string) $this->resource->ulid;
    }

    /**
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        return [
            'displayName' => $this->resource->display_name,

            'avatarUrl' => $this->resource->avatarUrl,
            'motto' => $this->resource->Motto,

            'points' => $this->resource->RASoftcorePoints,
            'pointsHardcore' => $this->resource->RAPoints,
            'pointsWeighted' => $this->resource->TrueRAPoints,

            'yieldUnlocks' => $this->resource->ContribCount,
            'yieldPoints' => $this->resource->ContribYield,

            'joinedAt' => $this->resource->Created,
            'lastActivityAt' => $this->resource->LastLogin,

            'isUnranked' => $this->resource->unranked_at !== null,
            'isUserWallActive' => (bool) $this->resource->UserWallActive,

            'richPresenceMessage' => $this->resource->RichPresenceMsg,
            'richPresenceUpdatedAt' => $this->resource->RichPresenceMsgDate,

            'visibleRole' => $this->resource->visibleRole?->name,
            'displayableRoles' => $this->resource->displayableRoles()
                ->get()
                ->pluck('name')
                ->values()
                ->all(),
        ];
    }

    /**
     * @param Request|null $request
     */
    public function relationships($request): iterable
    {
        return [
            // TODO add relationships
            // 'lastGame' => $this->relation('lastGame'),
            // 'playerGames' => $this->relation('playerGames'),
            // 'playerAchievementSets' => $this->relation('playerAchievementSets'),
            // 'playerAchievements' => $this->relation('playerAchievements'),
            // 'awards' => $this->relation('playerBadges'),
            // 'following' => $this->relation('followedUsers'),
            // 'followers' => $this->relation('followerUsers'),
            // 'authoredAchievements' => $this->relation('authoredAchievements'),
            // 'claims' => $this->relation('claims'),
        ];
    }

    /**
     * @param Request|null $request
     */
    public function links($request): Links
    {
        $selfLink = $this->selfLink();
        $profileLink = new Link('profile', route('user.show', $this->resource));

        return $selfLink
            ? new Links($selfLink, $profileLink)
            : new Links($profileLink);
    }
}
