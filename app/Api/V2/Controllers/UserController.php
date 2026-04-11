<?php

declare(strict_types=1);

namespace App\Api\V2\Controllers;

use App\Api\V2\UserAwards\UserAwardKind;
use App\Models\PlayerBadge;
use App\Models\User;
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
        $allAwards = PlayerBadge::query()
            ->canonicalForApiUser($user->id)
            ->orderedForProfile()
            ->with([
                'eventIfApplicable',
                'gameIfApplicable.system',
            ])
            ->get();

        $awardKindCounts = $allAwards
            ->map(fn (PlayerBadge $award) => UserAwardKind::fromAward($award)->value)
            ->countBy();

        $meta = [
            'beatenHardcoreAwardsCount' => $awardKindCounts->get(UserAwardKind::BeatenHardcore->value, 0),
            'beatenSoftcoreAwardsCount' => $awardKindCounts->get(UserAwardKind::BeatenSoftcore->value, 0),
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
}
