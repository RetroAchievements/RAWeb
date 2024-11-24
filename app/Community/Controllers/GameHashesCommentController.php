<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Concerns\IndexesComments;
use App\Community\Data\CommentData;
use App\Community\Data\GameHashesCommentsPagePropsData;
use App\Data\PaginatedData;
use App\Models\Comment;
use App\Models\Game;
use App\Models\GameHash;
use App\Platform\Data\GameData;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;

class GameHashesCommentController extends CommentController
{
    use IndexesComments;

    public function index(Game $game): InertiaResponse|RedirectResponse
    {
        $this->authorize('manage', [GameHash::class]);

        return $this->handleCommentIndex(
            commentable: $game,
            commentableType: 'hashes',
            policy: Comment::class,
            routeName: 'game.hashes.comment.index',
            routeParam: 'game',
            view: 'game/[game]/hashes/comments',
            createPropsData: function ($game, $paginatedComments, $isSubscribed, $user) {
                return new GameHashesCommentsPagePropsData(
                    game: GameData::fromGame($game)->include('badgeUrl', 'system'),
                    paginatedComments: PaginatedData::fromLengthAwarePaginator(
                        $paginatedComments,
                        total: $paginatedComments->total(),
                        items: CommentData::fromCollection($paginatedComments->getCollection())
                    ),
                    isSubscribed: false, // not subscribable
                    canComment: $user->can('manage', [GameHash::class]),
                );
            }
        );
    }
}
