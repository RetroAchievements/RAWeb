<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Concerns\IndexesComments;
use App\Community\Data\CommentData;
use App\Community\Data\EventCommentsPagePropsData;
use App\Data\PaginatedData;
use App\Models\Event;
use App\Models\EventComment;
use App\Platform\Data\EventData;
use App\Policies\EventCommentPolicy;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;

class EventCommentController extends CommentController
{
    use IndexesComments;

    public function index(Event $event): InertiaResponse|RedirectResponse
    {
        return $this->handleCommentIndex(
            commentable: $event,
            policy: EventComment::class,
            routeName: 'event.comment.index',
            routeParam: 'event',
            view: 'event/[event]/comments',
            createPropsData: function ($event, $paginatedComments, $isSubscribed, $user) {
                return new EventCommentsPagePropsData(
                    event: EventData::fromEvent($event)->include('legacyGame.badgeUrl'),
                    paginatedComments: PaginatedData::fromLengthAwarePaginator(
                        $paginatedComments,
                        total: $paginatedComments->total(),
                        items: CommentData::fromCollection($paginatedComments->getCollection())
                    ),
                    isSubscribed: $isSubscribed,
                    canComment: (new EventCommentPolicy())->create($user, $event)
                );
            }
        );
    }
}
