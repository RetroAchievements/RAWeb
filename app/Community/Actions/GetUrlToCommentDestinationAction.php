<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\CommentableType;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Event;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class GetUrlToCommentDestinationAction
{
    private const EMBEDDED_COMMENTS_LIMIT = 20;
    private const FULL_PAGE_COMMENTS_PER_PAGE = 50;

    /**
     * Builds the redirect URL for a given comment.
     *
     * If the comment is among the 20 most recent visible comments,
     * redirects to the resource page. Otherwise, redirects to the
     * full comments page at the correct pagination offset.
     */
    public function execute(Comment $comment): string
    {
        $commentable = $this->resolveCommentable($comment->commentable_type, $comment->commentable_id);

        abort_if($commentable === null, 404);

        // For user walls, check if the wall is active.
        abort_if($commentable instanceof User && !$commentable->is_user_wall_active, 404);

        $isVisibleOnResourcePage = $this->getIsCommentVisibleOnResourcePage($comment, $commentable);

        if ($isVisibleOnResourcePage) {
            return $this->buildResourcePageUrl($comment->commentable_type, $commentable, $comment->id);
        }

        return $this->buildFullCommentsPageUrl($comment, $commentable);
    }

    private function resolveCommentable(CommentableType $commentableType, int $commentableId): Achievement|Event|Game|Leaderboard|User|null
    {
        return match ($commentableType) {
            CommentableType::Achievement => Achievement::find($commentableId),
            CommentableType::Event => Event::find($commentableId),
            CommentableType::Game => Game::find($commentableId),
            CommentableType::Leaderboard => Leaderboard::find($commentableId),
            CommentableType::User => User::find($commentableId),
            default => null,
        };
    }

    private function getIsCommentVisibleOnResourcePage(
        Comment $comment,
        Achievement|Event|Game|Leaderboard|User $commentable,
    ): bool {
        /** @var ?User $currentUser */
        $currentUser = Auth::user();

        $mostRecentCommentIds = $commentable->visibleComments($currentUser)
            ->latest('created_at')
            ->limit(self::EMBEDDED_COMMENTS_LIMIT)
            ->pluck('id')
            ->toArray();

        return in_array($comment->id, $mostRecentCommentIds, true);
    }

    private function buildResourcePageUrl(
        CommentableType $commentableType,
        Achievement|Event|Game|Leaderboard|User $commentable,
        int $commentId,
    ): string {
        $hashAnchor = "#comment_{$commentId}";

        return match ($commentableType) {
            CommentableType::Achievement => route('achievement.show', ['achievementId' => $commentable->id]) . $hashAnchor,
            CommentableType::Event => route('event.show', ['event' => $commentable]) . $hashAnchor,
            CommentableType::Game => route('game.show', ['game' => $commentable, 'tab' => 'community']) . $hashAnchor,
            CommentableType::Leaderboard => route('leaderboard.show', ['leaderboard' => $commentable]) . $hashAnchor,
            CommentableType::User => route('user.show', $commentable) . $hashAnchor,
            default => abort(404),
        };
    }

    private function buildFullCommentsPageUrl(
        Comment $comment,
        Achievement|Event|Game|Leaderboard|User $commentable,
    ): string {
        $page = $this->calculateCommentPage($comment, $commentable);
        $hashAnchor = "#comment_{$comment->id}";

        $routeName = match ($comment->commentable_type) {
            CommentableType::Achievement => 'achievement.comment.index',
            CommentableType::Event => 'event.comment.index',
            CommentableType::Game => 'game.comment.index',
            CommentableType::Leaderboard => 'leaderboard.comment.index',
            CommentableType::User => 'user.comment.index',
            default => abort(404),
        };

        $routeParam = match ($comment->commentable_type) {
            CommentableType::Achievement => ['achievement' => $commentable, 'page' => $page],
            CommentableType::Event => ['event' => $commentable, 'page' => $page],
            CommentableType::Game => ['game' => $commentable, 'page' => $page],
            CommentableType::Leaderboard => ['leaderboard' => $commentable, 'page' => $page],
            CommentableType::User => ['user' => $commentable, 'page' => $page],
            default => abort(404),
        };

        return route($routeName, $routeParam) . $hashAnchor;
    }

    private function calculateCommentPage(
        Comment $comment,
        Achievement|Event|Game|Leaderboard|User $commentable,
    ): int {
        /** @var ?User $currentUser */
        $currentUser = Auth::user();

        // Comments are displayed in ascending order by created_at (oldest first).
        // Count how many comments come before this one.
        $positionFromStart = $commentable->visibleComments($currentUser)
            ->where('created_at', '<', $comment->created_at)
            ->count();

        // Position is 0-indexed, so add 1 for the comment itself.
        return (int) ceil(($positionFromStart + 1) / self::FULL_PAGE_COMMENTS_PER_PAGE);
    }
}
