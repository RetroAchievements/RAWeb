<?php

namespace App\Community\Concerns;

use App\Community\Enums\ArticleType;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

trait IndexesComments
{
    protected function handleCommentIndex(
        User|Game|Achievement|Leaderboard $commentable,
        string $policy,
        string $routeName,
        string $routeParam,
        string $view,
        callable $createPropsData,
        ?string $commentableType = null, // 'hashes' | 'claims' | 'modifications | 'moderation'
    ): InertiaResponse|RedirectResponse {
        $this->authorize('viewAny', [$policy, $commentable]);

        $perPage = 50;
        $currentPage = (int) request()->input('page', 1);

        $commentsQuery = $commentable->visibleComments();
        if ($commentable instanceof Game && $commentableType === 'hashes') {
            $commentsQuery = $commentable->visibleHashesComments();
        } elseif ($commentable instanceof Game && $commentableType === 'claims') {
            $commentsQuery = $commentable->visibleClaimsComments();
        } elseif ($commentable instanceof Game && $commentableType === 'modifications') {
            $commentsQuery = $commentable->visibleModificationsComments();
        } elseif ($commentable instanceof User && $commentableType === 'moderation') {
            $commentsQuery = $commentable->moderationComments();
        }

        // Get total comments to calculate the last page.
        $totalComments = $commentsQuery->count();
        $lastPage = (int) ceil($totalComments / $perPage);

        // If the current page exceeds the last page, redirect to the last page.
        if ($currentPage !== 1 && $currentPage > $lastPage) {
            return redirect()->route($routeName, [
                $routeParam => $commentable instanceof User ? $commentable->username : $commentable->id,
                'page' => $lastPage,
            ]);
        }

        $paginatedComments = $commentsQuery
            ->with(['user' => function ($query) {
                $query->withTrashed();
            }])
            ->paginate($perPage);

        /** @var ?User $user */
        $user = Auth::user();
        $isSubscribed = false;
        if ($user) {
            $articleType = match (true) {
                $commentable instanceof User => ArticleType::User,
                $commentable instanceof Game => ArticleType::Game,
                $commentable instanceof Achievement => ArticleType::Achievement,
                $commentable instanceof Leaderboard => ArticleType::Leaderboard,
            };
            $isSubscribed = isUserSubscribedToArticleComments($articleType, $commentable->id, $user->id);
        }

        $props = $createPropsData(
            $commentable,
            $paginatedComments,
            $isSubscribed,
            $user
        );

        return Inertia::render($view, $props);
    }
}
