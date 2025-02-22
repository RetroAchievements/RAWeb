<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\GetUrlToCommentDestinationAction;
use App\Community\Concerns\IndexesComments;
use App\Community\Data\AchievementCommentsPagePropsData;
use App\Community\Data\CommentData;
use App\Community\Requests\StoreCommentRequest;
use App\Data\PaginatedData;
use App\Models\Achievement;
use App\Models\AchievementComment;
use App\Platform\Data\AchievementData;
use App\Policies\AchievementCommentPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;

class AchievementCommentController extends CommentController
{
    use IndexesComments;

    public function index(Achievement $achievement): InertiaResponse|RedirectResponse
    {
        return $this->handleCommentIndex(
            commentable: $achievement,
            policy: AchievementComment::class,
            routeName: 'achievement.comment.index',
            routeParam: 'achievement',
            view: 'achievement/[achievement]/comments',
            createPropsData: function ($achievement, $paginatedComments, $isSubscribed, $user) {
                return new AchievementCommentsPagePropsData(
                    achievement: AchievementData::fromAchievement($achievement)->include('game.system'),
                    paginatedComments: PaginatedData::fromLengthAwarePaginator(
                        $paginatedComments,
                        total: $paginatedComments->total(),
                        items: CommentData::fromCollection($paginatedComments->getCollection())
                    ),
                    isSubscribed: $isSubscribed,
                    canComment: (new AchievementCommentPolicy())->create($user, $achievement)
                );
            }
        );
    }

    /**
     * @see UserCommentController::create()
     */
    public function create(): void
    {
    }

    public function store(): void
    {
    }

    public function edit(AchievementComment $comment): View
    {
        $this->authorize('update', $comment);

        return view('achievement.comment.edit')
            ->with('comment', $comment);
    }

    protected function update(
        StoreCommentRequest $request,
        AchievementComment $comment,
        GetUrlToCommentDestinationAction $getUrlToCommentDestinationAction
    ): RedirectResponse {
        $this->authorize('update', $comment);

        $comment->fill($request->validated())->save();

        return redirect($getUrlToCommentDestinationAction->execute($comment))
            ->with('success', $this->resourceActionSuccessMessage('achievement.comment', 'update'));
    }

    protected function destroy(AchievementComment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $return = $comment->commentable->canonicalUrl;

        /*
         * don't touch
         */
        $comment->timestamps = false;
        $comment->delete();

        return redirect($return)
            ->with('success', $this->resourceActionSuccessMessage('achievement.comment', 'delete'));
    }

    public function destroyAll(Achievement $achievement): RedirectResponse
    {
        $this->authorize('deleteComments', $achievement);

        $achievement->comments()->delete();

        return back()->with('success', $this->resourceActionSuccessMessage('achievement.comment', 'delete'));
    }
}
