<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Concerns\IndexesComments;
use App\Community\Data\CommentData;
use App\Community\Data\UserModerationCommentsPagePropsData;
use App\Data\PaginatedData;
use App\Data\UserData;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;

class UserModerationCommentController extends CommentController
{
    use IndexesComments;

    public function index(User $user): InertiaResponse|RedirectResponse
    {
        $this->authorize('manage', $user);

        return $this->handleCommentIndex(
            commentable: $user,
            commentableType: 'moderation',
            policy: Comment::class,
            routeName: 'user.moderation.comment.index',
            routeParam: 'user',
            view: 'user/[user]/moderation-comments',
            createPropsData: function ($user, $paginatedComments, $isSubscribed, $me) {
                return new UserModerationCommentsPagePropsData(
                    targetUser: UserData::fromUser($user)->include('id'),
                    paginatedComments: PaginatedData::fromLengthAwarePaginator(
                        $paginatedComments,
                        total: $paginatedComments->total(),
                        items: CommentData::fromCollection($paginatedComments->getCollection())
                    ),
                    isSubscribed: false, // not subscribable
                    canComment: $me->can('manage', $user),
                );
            }
        );
    }
}
