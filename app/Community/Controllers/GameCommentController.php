<?php

declare(strict_types=1);

namespace App\Community\Controllers;

use App\Community\Actions\GetUrlToCommentDestinationAction;
use App\Community\Data\CommentData;
use App\Community\Data\GameCommentsPagePropsData;
use App\Community\Data\SubscriptionData;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Requests\StoreCommentRequest;
use App\Data\PaginatedData;
use App\Models\Game;
use App\Models\GameComment;
use App\Models\Subscription;
use App\Models\User;
use App\Platform\Data\GameData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class GameCommentController extends CommentController
{
    public function index(Game $game): InertiaResponse|RedirectResponse
    {
        $this->authorize('viewAny', [GameComment::class, $game]);

        $perPage = 50;
        $currentPage = (int) request()->input('page', 1);

        // Get total comments to calculate the last page.
        $totalComments = $game->visibleComments()->count();
        $lastPage = (int) ceil($totalComments / $perPage);

        // If the current page exceeds the last page, redirect to the last page.
        if ($currentPage !== 1 && $currentPage > $lastPage) {
            return redirect()->route('game.comment.index', ['game' => $game->id, 'page' => $lastPage]);
        }

        $paginatedComments = $game->visibleComments()
            ->with(['user' => function ($query) {
                $query->withTrashed();
            }])
            ->paginate(50);

        /** @var ?User $user */
        $user = Auth::user();
        $subscription = null;
        if ($user) {
            $subscription = Subscription::whereUserId($user->id)
                ->whereSubjectType(SubscriptionSubjectType::GameWall)
                ->whereSubjectId($game->id)
                ->first();
        }

        $props = new GameCommentsPagePropsData(
            game: GameData::fromGame($game)->include('badgeUrl', 'system'),
            paginatedComments: PaginatedData::fromLengthAwarePaginator(
                $paginatedComments,
                total: $paginatedComments->total(),
                items: CommentData::fromCollection($paginatedComments->getCollection())
            ),
            subscription: $subscription ? SubscriptionData::from($subscription) : null,
        );

        return Inertia::render('game/[game]/comments', $props);
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
