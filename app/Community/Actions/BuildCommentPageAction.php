<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Data\CommentData;
use App\Community\Data\CommentPagePropsData;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Services\SubscriptionService;
use App\Data\PaginatedData;
use App\Data\UserPermissionsData;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class BuildCommentPageAction
{
    public function execute(
        User|Game|Achievement|Leaderboard $commentable,
        string $view,
        string $policyClass,
        string $entityKey,
        Closure $createEntityData,
        ?string $routeName = null,
        ?string $commentableType = null,
        bool $isSubscribable = true,
        ?Closure $canCommentCheck = null,
    ): InertiaResponse|RedirectResponse {
        $perPage = 50;
        $currentPage = (int) request()->input('page', 1);

        $commentsQuery = $this->buildCommentsQuery($commentable, $commentableType);
        $totalComments = $commentsQuery->count();
        $lastPage = max(1, (int) ceil($totalComments / $perPage));

        // Redirect if page exceeds the last page.
        if ($currentPage !== 1 && $currentPage > $lastPage) {
            $routeParam = $commentable instanceof User ? 'user' : strtolower(class_basename($commentable));
            $routeValue = $commentable instanceof User ? $commentable->username : $commentable->id;
            $derivedRoute = $routeName ?? "{$routeParam}.comment.index";

            return redirect()->route($derivedRoute, [
                $routeParam => $routeValue,
                'page' => $lastPage,
            ]);
        }

        $paginatedComments = $commentsQuery
            ->with([
                'user' => fn ($query) => $query->withTrashed(),
            ])
            ->paginate($perPage);

        /** @var ?User $user */
        $user = Auth::user();

        $isSubscribed = $isSubscribable ? $this->checkSubscription($user, $commentable) : false;

        $canComment = $canCommentCheck
            ? $canCommentCheck($user, $commentable)
            : ($user ? $user->can('create', [$policyClass, $commentable]) : false);

        return Inertia::render($view, new CommentPagePropsData(
            can: UserPermissionsData::fromUser($user)->include('manageUsers'),
            canComment: $canComment,
            isSubscribed: $isSubscribed,
            paginatedComments: PaginatedData::fromLengthAwarePaginator(
                $paginatedComments,
                total: $paginatedComments->total(),
                items: CommentData::fromCollection($paginatedComments->getCollection())
            ),
            entity: $createEntityData($commentable),
            entityKey: $entityKey,
        ));
    }

    /**
     * @return HasMany<Comment, covariant User|Game|Achievement|Leaderboard>
     */
    private function buildCommentsQuery(
        User|Game|Achievement|Leaderboard $commentable,
        ?string $commentableType,
    ): HasMany {
        if ($commentable instanceof Game) {
            return match ($commentableType) {
                'hashes' => $commentable->visibleHashesComments(),
                'claims' => $commentable->visibleClaimsComments(),
                'modifications' => $commentable->visibleModificationsComments(),
                default => $commentable->visibleComments(),
            };
        }

        if ($commentable instanceof User && $commentableType === 'moderation') {
            return $commentable->moderationComments();
        }

        return $commentable->visibleComments();
    }

    private function checkSubscription(?User $user, User|Game|Achievement|Leaderboard $commentable): bool
    {
        if (!$user) {
            return false;
        }

        $subjectType = match (true) {
            $commentable instanceof User => SubscriptionSubjectType::UserWall,
            $commentable instanceof Game => SubscriptionSubjectType::GameWall,
            $commentable instanceof Achievement => SubscriptionSubjectType::Achievement,
            $commentable instanceof Leaderboard => SubscriptionSubjectType::Leaderboard,
        };

        return (new SubscriptionService())->isSubscribed($user, $subjectType, $commentable->id);
    }
}
