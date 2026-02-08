<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\BuildCommentPageAction;
use App\Models\Game;
use App\Models\GameComment;
use App\Platform\Data\GameData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;

class GameCommentController extends CommentController
{
    public function index(Game $game, BuildCommentPageAction $action): InertiaResponse|RedirectResponse
    {
        $this->authorize('viewAny', [GameComment::class, $game]);

        return $action->execute(
            $game,
            view: 'game/[game]/comments',
            policyClass: GameComment::class,
            entityKey: 'game',
            createEntityData: fn ($g) => GameData::fromGame($g)->include('badgeUrl', 'system'),
        );
    }

    /**
     * @see UserCommentController::create()
     */
    public function create(): void
    {
    }

    public function store(): void
    {
    }

    public function edit(GameComment $comment): View
    {
        $this->authorize('update', $comment);

        return view('game.comment.edit')
            ->with('comment', $comment);
    }

    protected function update(): void
    {
    }

    protected function destroy(GameComment $comment): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $return = $comment->commentable->canonicalUrl;

        /*
         * don't touch
         */
        $comment->timestamps = false;
        $comment->delete();

        return redirect($return)
            ->with('success', $this->resourceActionSuccessMessage('game.comment', 'delete'));
    }

    public function destroyAll(Game $game): RedirectResponse
    {
        $this->authorize('deleteComments', $game);

        $game->comments()->delete();

        return back()->with('success', $this->resourceActionSuccessMessage('game.comment', 'delete'));
    }
}
