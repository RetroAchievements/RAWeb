<?php

declare(strict_types=1);

namespace App\Api\V2\Controllers;

use App\Api\V2\UserAwards\UserAwardKind;
use App\Api\V2\UserFollows\UserFollowResource;
use App\Community\Enums\UserRelationStatus;
use App\Models\PlayerBadge;
use App\Models\User;
use App\Models\UserRelation;
use App\Policies\UserCommentPolicy;
use Illuminate\Support\Collection;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use LaravelJsonApi\Core\Pagination\Page;
use LaravelJsonApi\Core\Responses\RelatedResponse;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;

class UserController extends JsonApiController
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\FetchRelated;

    protected function readRelatedAwards(
        User $user,
        Page $data,
        ResourceQuery $request,
    ): RelatedResponse {
        $allAwardsQuery = PlayerBadge::query()
            ->canonicalForApiUser($user->id)
            ->orderedForProfile()
            ->with([
                'eventIfApplicable',
                'gameIfApplicable.system',
            ]);

        if ($request->filter()?->value('gameAwardTier') === 'highest') {
            $allAwardsQuery->highestGameAwardPerGame();
        }

        $allAwards = $allAwardsQuery->get();

        $awardKindCounts = $allAwards
            ->map(fn (PlayerBadge $award) => UserAwardKind::fromAward($award)->value)
            ->countBy();

        $meta = [
            'beatenCasualAwardsCount' => $awardKindCounts->get(UserAwardKind::BeatenCasual->value, 0),
            'beatenHardcoreAwardsCount' => $awardKindCounts->get(UserAwardKind::BeatenHardcore->value, 0),
            'completionAwardsCount' => $awardKindCounts->get(UserAwardKind::Completed->value, 0),
            'eventAwardsCount' => $allAwards->filter(fn (PlayerBadge $award) => $award->isCountedAsEventAward())->count(),
            'hiddenAwardsCount' => $allAwards->reject(fn (PlayerBadge $award) => $award->isVisibleOnUserProfile())->count(),
            'masteryAwardsCount' => $awardKindCounts->get(UserAwardKind::Mastered->value, 0),
            'siteAwardsCount' => $allAwards->filter(fn (PlayerBadge $award) => $award->isCountedAsSiteAward())->count(),
            'totalAwardsCount' => $allAwards->count(),
        ];

        return RelatedResponse::make($user, 'awards', $data)
            ->withMeta($meta)
            ->withQueryParameters($request);
    }

    protected function readingWallComments(
        User $user,
        ResourceQuery $request,
    ): void {
        $this->abortIfWallCommentsAreHidden($user, $request);
    }

    protected function readingRelatedWallComments(
        User $user,
        ResourceQuery $request,
    ): void {
        $this->abortIfWallCommentsAreHidden($user, $request);
    }

    /**
     * Precompute reciprocal follow ids for `isMutual`.
     */
    protected function readingRelatedFollowers(
        User $user,
        ResourceQuery $request,
    ): void {
        $this->stashReciprocalUserIds(
            UserRelation::query()
                ->where('status', '=', UserRelationStatus::Following)
                ->where('user_id', $user->id)
                ->pluck('related_user_id'),
        );
    }

    protected function readingRelatedFollowing(
        User $user,
        ResourceQuery $request,
    ): void {
        $this->stashReciprocalUserIds(
            UserRelation::query()
                ->where('status', '=', UserRelationStatus::Following)
                ->where('related_user_id', $user->id)
                ->pluck('user_id'),
        );
    }

    /**
     * @param Collection<int, int> $ids
     */
    private function stashReciprocalUserIds(Collection $ids): void
    {
        request()->attributes->set(
            UserFollowResource::RECIPROCAL_IDS_ATTRIBUTE,
            array_fill_keys($ids->all(), true),
        );
    }

    private function abortIfWallCommentsAreHidden(User $user, ResourceQuery $request): void
    {
        if ((new UserCommentPolicy())->viewAny($request->user(), $user)) {
            return;
        }

        throw JsonApiException::error([
            'status' => '404',
            'code' => 'not_found',
            'title' => 'Not Found',
            'detail' => "No comments found for user {$user->display_name}.",
        ]);
    }
}
