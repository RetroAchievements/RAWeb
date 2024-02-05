<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\AddCommentAction;
use App\Community\Actions\GetUrlToCommentDestinationAction;
use App\Community\Requests\CommentRequest;
use App\Models\Comment;
use App\Models\Game;
use App\Models\GameComment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class GameCommentController extends CommentController
{
    public function index(Game $game): View
    {
        $this->authorize('viewAny', [GameComment::class, $game]);

        return view('game.comment.index')
            ->with('game', $game);
    }

    /**
     * @see UserCommentController::create()
     */
    public function create(): void
    {
    }

    public function store(
        CommentRequest $request,
        Game $game,
        AddCommentAction $addCommentAction,
        GetUrlToCommentDestinationAction $getUrlToCommentDestinationAction
    ): RedirectResponse {
        $this->authorize('create', [GameComment::class, $game]);

        /** @var false|Comment $comment */
        $comment = $addCommentAction->execute($request, $game);

        if (!$comment) {
            return back()->with('error', $this->resourceActionErrorMessage('game.comment', 'create'));
        }

        return redirect($getUrlToCommentDestinationAction->execute($comment))
            ->with('success', $this->resourceActionSuccessMessage('game.comment', 'create'));
    }

    public function edit(GameComment $comment): View
    {
        $this->authorize('update', $comment);

        return view('game.comment.edit')
            ->with('comment', $comment);
    }

    protected function update(
        CommentRequest $request,
        GameComment $comment,
        GetUrlToCommentDestinationAction $getUrlToCommentDestinationAction
    ): RedirectResponse {
        $this->authorize('update', $comment);

        $comment->fill($request->validated())->save();

        return redirect($getUrlToCommentDestinationAction->execute($comment))
            ->with('success', $this->resourceActionSuccessMessage('game.comment', 'update'));
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
