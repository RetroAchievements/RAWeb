<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\BuildCommentPageAction;
use App\Models\AchievementSetClaim;
use App\Models\Comment;
use App\Models\Game;
use App\Platform\Data\GameData;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;

class GameClaimsCommentController extends CommentController
{
    public function index(Game $game, BuildCommentPageAction $action): InertiaResponse|RedirectResponse
    {
        $this->authorize('manage', [AchievementSetClaim::class]);

        return $action->execute(
            $game,
            view: 'game/[game]/claims/comments',
            policyClass: Comment::class,
            entityKey: 'game',
            createEntityData: fn ($g) => GameData::fromGame($g)->include('badgeUrl', 'system'),
            routeName: 'game.claims.comment.index',
            commentableType: 'claims',
            isSubscribable: false,
            canCommentCheck: fn ($user, $g) => $user?->can('manage', [AchievementSetClaim::class]) ?? false,
        );
    }
}
