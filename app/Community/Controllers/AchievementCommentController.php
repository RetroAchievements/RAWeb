<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\AddCommentAction;
use App\Community\Actions\GetUrlToCommentDestinationAction;
use App\Community\Models\AchievementComment;
use App\Community\Models\Comment;
use App\Community\Requests\CommentRequest;
use App\Models\Achievement;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AchievementCommentController extends CommentController
{
    public function index(Achievement $achievement): View
    {
        $this->authorize('viewAny', [AchievementComment::class, $achievement]);

        $achievement->loadMissing([
            'game',
            'user',
        ]);

        return view('achievement.comment.index')
            ->with('achievement', $achievement);
    }

    /**
     * @see UserCommentController::create()
     */
    public function create(): void
    {
    }

    public function store(
        CommentRequest $request,
        Achievement $achievement,
        AddCommentAction $addCommentAction,
        GetUrlToCommentDestinationAction $getUrlToCommentDestinationAction
    ): RedirectResponse {
        $this->authorize('create', [AchievementComment::class, $achievement]);

        /** @var false|Comment $comment */
        $comment = $addCommentAction->execute($request, $achievement);

        if (!$comment) {
            return back()->with('error', $this->resourceActionErrorMessage('achievement.comment', 'create'));
        }

        return redirect($getUrlToCommentDestinationAction->execute($comment))
            ->with('success', $this->resourceActionSuccessMessage('achievement.comment', 'create'));
    }

    public function edit(AchievementComment $comment): View
    {
        $this->authorize('update', $comment);

        return view('achievement.comment.edit')
            ->with('comment', $comment);
    }

    protected function update(
        CommentRequest $request,
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
