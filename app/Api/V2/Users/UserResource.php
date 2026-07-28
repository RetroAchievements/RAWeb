<?php

declare(strict_types=1);

namespace App\Api\V2\Users;

use App\Models\User;
use App\Platform\Enums\GlobalRankingMode;
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
        $userFieldset = $this->requestedUserFieldset($request);
        $shouldIncludeVisibleRole = $userFieldset === null || in_array('visibleRole', $userFieldset, true);
        $shouldIncludeDisplayableRoles = $userFieldset === null || in_array('displayableRoles', $userFieldset, true);
        $shouldIncludeRanks = $userFieldset === null || array_intersect(['rankHardcore', 'rankCasual'], $userFieldset) !== [];

        return [
            'displayName' => $this->resource->display_name,

            'avatarUrl' => $this->resource->avatarUrl,
            'motto' => $this->resource->motto,

            'points' => $this->resource->points,
            'pointsHardcore' => $this->resource->points_hardcore,
            'pointsWeighted' => $this->resource->points_weighted,
            'rankHardcore' => $this->when($shouldIncludeRanks, fn (): ?int => $this->rankFor(GlobalRankingMode::Hardcore)),
            'rankCasual' => $this->when($shouldIncludeRanks, fn (): ?int => $this->rankFor(GlobalRankingMode::Casual)),

            'yieldUnlocks' => $this->resource->yield_unlocks,
            'yieldPoints' => $this->resource->yield_points,

            'joinedAt' => $this->resource->trashed() ? null : $this->resource->created_at,
            'lastActivityAt' => $this->resource->last_activity_at,
            'deletedAt' => $this->when($this->resource->trashed(), $this->resource->deleted_at),

            'isUnranked' => $this->resource->unranked_at !== null,
            'isUserWallActive' => (bool) $this->resource->is_user_wall_active,

            'richPresence' => $this->resource->rich_presence,
            'richPresenceUpdatedAt' => $this->resource->rich_presence_updated_at,

            'visibleRole' => $this->when(
                $shouldIncludeVisibleRole,
                fn () => $this->resource->visibleRole?->name,
            ),
            'displayableRoles' => $this->when(
                $shouldIncludeDisplayableRoles,
                fn () => ($this->resource->relationLoaded('roles')
                    ? $this->resource->roles->where('display', '>', 0)
                    : $this->resource->displayableRoles()->get())
                    ->pluck('name')
                    ->values()
                    ->all(),
            ),
        ];
    }

    /**
     * @param Request|null $request
     */
    public function relationships($request): iterable
    {
        return [
            'awards' => $this->relation('awards', 'playerBadges')->withoutLinks(),
            'followers' => $this->relation('followers', 'followedBy')->withoutLinks(),
            'following' => $this->relation('following', 'follows')->withoutLinks(),
            'lastGame' => $this->relation('lastGame')->withoutLinks()->showDataIfLoaded(),
            'playerAchievements' => $this->relation('playerAchievements')->withoutLinks(),
            'playerAchievementSets' => $this->relation('playerAchievementSets')->withoutLinks(),
            'playerGames' => $this->relation('playerGames')->withoutLinks(),

            // TODO add relationships
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
        $webLink = new Link('webUrl', route('user.show', $this->resource));

        return $selfLink
            ? new Links($selfLink, $webLink)
            : new Links($webLink);
    }

    /**
     * The sparse fieldset requested for users, or null when the client wants all fields.
     *
     * @return array<int, string>|null
     */
    private function requestedUserFieldset(?Request $request): ?array
    {
        $requestedUserFields = $request?->input('fields.users');

        return is_string($requestedUserFields)
            ? array_map('trim', explode(',', $requestedUserFields))
            : null;
    }

    private function rankFor(GlobalRankingMode $mode): ?int
    {
        if ($this->resource->unranked_at !== null) {
            return null;
        }

        $ranking = $this->resource->allTimeGlobalRankings
            ->firstWhere('mode', $mode);

        return $ranking?->rank_number;
    }
}
