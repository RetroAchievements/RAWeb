<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\BuildCommentPageAction;
use App\Models\Comment;
use App\Models\Game;
use App\Models\GameHash;
use App\Platform\Data\GameData;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;

class GameHashesCommentController extends CommentController
{
    public function index(Game $game, BuildCommentPageAction $action): InertiaResponse|RedirectResponse
    {
        $this->authorize('manage', [GameHash::class]);

        return $action->execute(
            $game,
            view: 'game/[game]/hashes/comments',
            policyClass: Comment::class,
            entityKey: 'game',
            createEntityData: fn ($g) => GameData::fromGame($g)->include('badgeUrl', 'system'),
            routeName: 'game.hashes.comment.index',
            commentableType: 'hashes',
            isSubscribable: false,
            canCommentCheck: fn ($user, $g) => $user?->can('manage', [GameHash::class]) ?? false,
        );
    }
}
