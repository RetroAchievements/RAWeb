<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\GetUrlToCommentDestinationAction;
use App\Community\Concerns\IndexesComments;
use App\Community\Data\CommentData;
use App\Community\Data\UserCommentsPagePropsData;
use App\Community\Enums\ArticleType;
use App\Community\Requests\StoreCommentRequest;
use App\Data\PaginatedData;
use App\Data\UserData;
use App\Models\Comment;
use App\Models\User;
use App\Models\UserComment;
use App\Policies\UserCommentPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

class UserCommentController extends CommentController
{
    use IndexesComments;

    public function index(User $user): InertiaResponse|RedirectResponse
    {
        return $this->handleCommentIndex(
            commentable: $user,
            policy: UserComment::class,
            routeName: 'user.comment.index',
            routeParam: 'user',
            view: 'user/[user]/comments',
            createPropsData: function ($user, $paginatedComments, $isSubscribed, $me) {
                return new UserCommentsPagePropsData(
                    targetUser: UserData::fromUser($user)->include('id'),
                    paginatedComments: PaginatedData::fromLengthAwarePaginator(
                        $paginatedComments,
                        total: $paginatedComments->total(),
                        items: CommentData::fromCollection($paginatedComments->getCollection())
                    ),
                    isSubscribed: $isSubscribed,
                    canComment: $me ? (new UserCommentPolicy())->create($me, $user) : false
                );
            }
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

    protected function update(
        StoreCommentRequest $request,
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
