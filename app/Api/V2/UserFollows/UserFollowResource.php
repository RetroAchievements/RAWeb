<?php

declare(strict_types=1);

namespace App\Api\V2\UserFollows;

use App\Api\V2\BaseJsonApiResource;
use App\Models\User;
use App\Models\UserRelation;
use Illuminate\Http\Request;
use LaravelJsonApi\Core\Document\Links;

/**
 * @property UserRelation $resource
 */
class UserFollowResource extends BaseJsonApiResource
{
    public const RECIPROCAL_IDS_ATTRIBUTE = 'userFollowReciprocalUserIds';

    /**
     * @param Request|null $request
     */
    public function attributes($request): iterable
    {
        $presenter = UserFollowPresenter::for(
            $this->resource,
            $this->perspectiveUser(),
            $this->reciprocalUserIds(),
        );

        return [
            'followedAt' => $this->resource->created_at,

            'userId' => $presenter->userId(),
            'displayName' => $presenter->displayName(),
            'avatarUrl' => $presenter->avatarUrl(),
            'points' => $presenter->points(),
            'pointsHardcore' => $presenter->pointsHardcore(),
            'isMutual' => $presenter->isMutual(),
        ];
    }

    /**
     * @param Request|null $request
     */
    public function relationships($request): iterable
    {
        if (!$this->wasRelationshipIncluded($request, 'user')) {
            return [];
        }

        $presenter = UserFollowPresenter::for(
            $this->resource,
            $this->perspectiveUser(),
            $this->reciprocalUserIds(),
        );

        return [
            'user' => $this->relation('user', 'relatedUser')
                ->withoutLinks()
                ->withData($presenter->user())
                ->alwaysShowData(),
        ];
    }

    /**
     * @param Request|null $request
     */
    public function links($request): Links
    {
        return new Links();
    }

    private function perspectiveUser(): User
    {
        /** @var User $user */
        $user = request()->user();

        return $user;
    }

    /**
     * @return array<int, bool>
     */
    private function reciprocalUserIds(): array
    {
        $ids = request()->attributes->get(self::RECIPROCAL_IDS_ATTRIBUTE, []);

        return is_array($ids) ? $ids : [];
    }
}
