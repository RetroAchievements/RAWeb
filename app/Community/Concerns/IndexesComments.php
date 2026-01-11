<?php

namespace App\Community\Concerns;

use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Services\SubscriptionService;
use App\Models\Achievement;
use App\Models\Event;
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
        Achievement|Event|Game|Leaderboard|User $commentable,
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
            $subjectType = match (true) {
                $commentable instanceof Achievement => SubscriptionSubjectType::Achievement,
                $commentable instanceof Event => SubscriptionSubjectType::EventWall,
                $commentable instanceof Game => SubscriptionSubjectType::GameWall,
                $commentable instanceof Leaderboard => SubscriptionSubjectType::Leaderboard,
                $commentable instanceof User => SubscriptionSubjectType::UserWall,
            };
            $isSubscribed = (new SubscriptionService())->isSubscribed($user, $subjectType, $commentable->id);
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
