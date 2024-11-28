<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Concerns\IndexesComments;
use App\Community\Data\CommentData;
use App\Community\Data\GameClaimsCommentsPagePropsData;
use App\Data\PaginatedData;
use App\Models\AchievementSetClaim;
use App\Models\Comment;
use App\Models\Game;
use App\Platform\Data\GameData;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;

class GameClaimsCommentController extends CommentController
{
    use IndexesComments;

    public function index(Game $game): InertiaResponse|RedirectResponse
    {
        $this->authorize('manage', [AchievementSetClaim::class]);

        return $this->handleCommentIndex(
            commentable: $game,
            commentableType: 'claims',
            policy: Comment::class,
            routeName: 'game.claims.comment.index',
            routeParam: 'game',
            view: 'game/[game]/claims/comments',
            createPropsData: function ($game, $paginatedComments, $isSubscribed, $user) {
                return new GameClaimsCommentsPagePropsData(
                    game: GameData::fromGame($game)->include('badgeUrl', 'system'),
                    paginatedComments: PaginatedData::fromLengthAwarePaginator(
                        $paginatedComments,
                        total: $paginatedComments->total(),
                        items: CommentData::fromCollection($paginatedComments->getCollection())
                    ),
                    isSubscribed: false, // not subscribable
                    canComment: $user->can('manage', [AchievementSetClaim::class]),
                );
            }
        );
    }
}
