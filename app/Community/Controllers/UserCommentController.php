<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\AddCommentAction;
use App\Community\Actions\GetUrlToCommentDestinationAction;
use App\Community\Enums\ArticleType;
use App\Community\Requests\CommentRequest;
use App\Models\Comment;
use App\Models\User;
use App\Models\UserComment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserCommentController extends CommentController
{
    public function index(User $user): View
    {
        $this->authorize('viewAny', [UserComment::class, $user]);

        $comments = UserComment::paginate();

        return view('user.comment.index')
            ->with('user', $user)
            ->with('grid', $comments);
    }

    /**
     * There is no create form for creating a new comment.
     * comments have to be created for something -> use sub resource create route, e.g.
     * - user.comment.create (wall)
     * - achievement-ticket.comment.create
     * - forum-topic-comment.create
     */
    public function create(): void
    {
    }

    public function store(
        CommentRequest $request,
        User $user,
        AddCommentAction $addCommentAction,
        GetUrlToCommentDestinationAction $getUrlToCommentDestinationAction
    ): RedirectResponse {
        $this->authorize('create', [UserComment::class, $user]);

        /** @var false|Comment $comment */
        $comment = $addCommentAction->execute($request, $user);

        if (!$comment) {
            return back()->with('error', $this->resourceActionErrorMessage('user.comment', 'create'));
        }

        return redirect($getUrlToCommentDestinationAction->execute($comment))
            ->with('success', $this->resourceActionSuccessMessage('user.comment', 'create'));
    }

    public function edit(UserComment $comment): View
    {
        $this->authorize('update', $comment);

        $comment->commentable->loadMissing('lastActivity');

        return view('user.comment.edit')
            ->with('comment', $comment);
    }

    protected function update(
        CommentRequest $request,
        UserComment $comment,
        GetUrlToCommentDestinationAction $getUrlToCommentDestinationAction
    ): RedirectResponse {
        $this->authorize('update', $comment);

        $comment->fill($request->validated())->save();

        return redirect($getUrlToCommentDestinationAction->execute($comment))
            ->with('success', $this->resourceActionSuccessMessage('user.comment', 'update'));
    }

    protected function destroy(UserComment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $return = $comment->commentable->canonicalUrl;

        /*
         * don't touch
         */
        $comment->timestamps = false;
        $comment->delete();

        return redirect($return)
            ->with('success', $this->resourceActionSuccessMessage('user.comment', 'delete'));
    }

    public function destroyAll(Request $request, int $targetUserId): JsonResponse
    {
        $targetUser = User::findOrFail($targetUserId);
        $this->authorize('clearUserWall', $targetUser);

        Comment::where('ArticleType', ArticleType::User)
            ->where('ArticleID', $targetUser->id)
            ->delete();

        return response()->json(['success' => true]);
    }
}
