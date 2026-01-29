<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\BuildCommentPageAction;
use App\Models\Achievement;
use App\Models\AchievementComment;
use App\Platform\Data\AchievementData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;

class AchievementCommentController extends CommentController
{
    public function index(Achievement $achievement, BuildCommentPageAction $action): InertiaResponse|RedirectResponse
    {
        $this->authorize('viewAny', [AchievementComment::class, $achievement]);

        return $action->execute(
            $achievement,
            view: 'achievement/[achievement]/comments',
            policyClass: AchievementComment::class,
            entityKey: 'achievement',
            createEntityData: fn ($a) => AchievementData::fromAchievement($a)->include('game.system'),
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

    protected function update(): void
    {
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
