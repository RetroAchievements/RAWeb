<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\GetUrlToCommentDestinationAction;
use App\Community\Concerns\IndexesComments;
use App\Community\Data\CommentData;
use App\Community\Data\GameCommentsPagePropsData;
use App\Community\Requests\StoreCommentRequest;
use App\Data\PaginatedData;
use App\Models\Game;
use App\Models\GameComment;
use App\Platform\Data\GameData;
use App\Policies\GameCommentPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Inertia\Response as InertiaResponse;

class GameCommentController extends CommentController
{
    use IndexesComments;

    public function index(Game $game): InertiaResponse|RedirectResponse
    {
        return $this->handleCommentIndex(
            commentable: $game,
            policy: GameComment::class,
            routeName: 'game.comment.index',
            routeParam: 'game',
            view: 'game/[game]/comments',
            createPropsData: function ($game, $paginatedComments, $isSubscribed, $user) {
                return new GameCommentsPagePropsData(
                    game: GameData::fromGame($game)->include('badgeUrl', 'system'),
                    paginatedComments: PaginatedData::fromLengthAwarePaginator(
                        $paginatedComments,
                        total: $paginatedComments->total(),
                        items: CommentData::fromCollection($paginatedComments->getCollection())
                    ),
                    isSubscribed: $isSubscribed,
                    canComment: (new GameCommentPolicy())->create($user, $game)
                );
            }
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

    protected function update(
        StoreCommentRequest $request,
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
