<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\BuildCommentPageAction;
use App\Data\UserData;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;

class UserModerationCommentController extends CommentController
{
    public function index(User $user, BuildCommentPageAction $action): InertiaResponse|RedirectResponse
    {
        $this->authorize('manage', $user);

        return $action->execute(
            $user,
            view: 'user/[user]/moderation-comments',
            policyClass: Comment::class,
            entityKey: 'targetUser',
            createEntityData: fn ($u) => UserData::fromUser($u)->include('id'),
            routeName: 'user.moderation.comment.index',
            commentableType: 'moderation',
            isSubscribable: false,
            canCommentCheck: fn ($me, $u) => $me?->can('manage', $u) ?? false,
        );
    }
}
