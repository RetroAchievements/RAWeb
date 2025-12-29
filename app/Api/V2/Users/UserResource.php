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
            'motto' => $this->resource->motto,

            'points' => $this->resource->points,
            'pointsSoftcore' => $this->resource->points_softcore,
            'pointsWeighted' => $this->resource->points_weighted,

            'yieldUnlocks' => $this->resource->yield_unlocks,
            'yieldPoints' => $this->resource->yield_points,

            'joinedAt' => $this->resource->created_at,
            'lastActivityAt' => $this->resource->last_activity_at,

            'isUnranked' => $this->resource->unranked_at !== null,
            'isUserWallActive' => (bool) $this->resource->is_user_wall_active,

            'richPresenceMessage' => $this->resource->rich_presence,
            'richPresenceUpdatedAt' => $this->resource->rich_presence_updated_at,

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
