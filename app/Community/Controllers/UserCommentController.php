<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\BuildCommentPageAction;
use App\Community\Enums\CommentableType;
use App\Data\UserData;
use App\Models\Comment;
use App\Models\User;
use App\Models\UserComment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

class UserCommentController extends CommentController
{
    public function index(User $user, BuildCommentPageAction $action): InertiaResponse|RedirectResponse
    {
        $this->authorize('viewAny', [UserComment::class, $user]);

        return $action->execute(
            $user,
            view: 'user/[user]/comments',
            policyClass: UserComment::class,
            entityKey: 'targetUser',
            createEntityData: fn ($u) => UserData::fromUser($u)->include('id'),
        );
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

    public function store(): void
    {
    }

    public function edit(UserComment $comment): View
    {
        $this->authorize('update', $comment);

        $comment->commentable->loadMissing('lastActivity');

        return view('user.comment.edit')
            ->with('comment', $comment);
    }

    protected function update(): void
    {
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

        Comment::where('commentable_type', CommentableType::User)
            ->where('commentable_id', $targetUser->id)
            ->delete();

        return response()->json(['success' => true]);
    }
}
