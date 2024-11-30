<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Concerns\IndexesComments;
use App\Community\Data\CommentData;
use App\Community\Data\GameModificationsCommentsPagePropsData;
use App\Data\PaginatedData;
use App\Models\Comment;
use App\Models\Game;
use App\Platform\Data\GameData;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;

class GameModificationsCommentController extends CommentController
{
    use IndexesComments;

    public function index(Game $game): InertiaResponse|RedirectResponse
    {
        $this->authorize('develop');

        return $this->handleCommentIndex(
            commentable: $game,
            commentableType: 'modifications',
            policy: Comment::class,
            routeName: 'game.modifications.comment.index',
            routeParam: 'game',
            view: 'game/[game]/modification-comments',
            createPropsData: function ($game, $paginatedComments, $isSubscribed, $user) {
                return new GameModificationsCommentsPagePropsData(
                    game: GameData::fromGame($game)->include('badgeUrl', 'system'),
                    paginatedComments: PaginatedData::fromLengthAwarePaginator(
                        $paginatedComments,
                        total: $paginatedComments->total(),
                        items: CommentData::fromCollection($paginatedComments->getCollection())
                    ),
                    isSubscribed: false, // not subscribable
                    canComment: $user->can('develop'),
                );
            }
        );
    }
}