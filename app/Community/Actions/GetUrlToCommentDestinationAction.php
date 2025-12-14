<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\ArticleType;
use App\Models\Achievement;
use App\Models\Comment;
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
        $commentable = $this->resolveCommentable($comment->ArticleType, $comment->ArticleID);

        abort_if($commentable === null, 404);

        // For user walls, check if the wall is active.
        abort_if($commentable instanceof User && !$commentable->UserWallActive, 404);

        $isVisibleOnResourcePage = $this->getIsCommentVisibleOnResourcePage($comment, $commentable);

        if ($isVisibleOnResourcePage) {
            return $this->buildResourcePageUrl($comment->ArticleType, $commentable, $comment->ID);
        }

        return $this->buildFullCommentsPageUrl($comment, $commentable);
    }

    private function resolveCommentable(int $articleType, int $articleId): Game|Achievement|User|Leaderboard|null
    {
        return match ($articleType) {
            ArticleType::Game => Game::find($articleId),
            ArticleType::Achievement => Achievement::find($articleId),
            ArticleType::User => User::find($articleId),
            ArticleType::Leaderboard => Leaderboard::find($articleId),
            default => null,
        };
    }

    private function getIsCommentVisibleOnResourcePage(
        Comment $comment,
        Game|Achievement|User|Leaderboard $commentable,
    ): bool {
        /** @var ?User $currentUser */
        $currentUser = Auth::user();

        $mostRecentCommentIds = $commentable->visibleComments($currentUser)
            ->latest('Submitted')
            ->limit(self::EMBEDDED_COMMENTS_LIMIT)
            ->pluck('ID')
            ->toArray();

        return in_array($comment->ID, $mostRecentCommentIds, true);
    }

    private function buildResourcePageUrl(
        int $articleType,
        Game|Achievement|User|Leaderboard $commentable,
        int $commentId,
    ): string {
        $hashAnchor = "#comment_{$commentId}";

        return match ($articleType) {
            ArticleType::Game => route('game.show', ['game' => $commentable, 'tab' => 'community']) . $hashAnchor,
            ArticleType::Achievement => route('achievement.show', ['achievementId' => $commentable->id]) . $hashAnchor,
            ArticleType::User => route('user.show', $commentable) . $hashAnchor,
            ArticleType::Leaderboard => route('leaderboard.show', ['leaderboard' => $commentable]) . $hashAnchor,
            default => abort(404),
        };
    }

    private function buildFullCommentsPageUrl(
        Comment $comment,
        Game|Achievement|User|Leaderboard $commentable,
    ): string {
        $page = $this->calculateCommentPage($comment, $commentable);
        $hashAnchor = "#comment_{$comment->ID}";

        $routeName = match ($comment->ArticleType) {
            ArticleType::Game => 'game.comment.index',
            ArticleType::Achievement => 'achievement.comment.index',
            ArticleType::User => 'user.comment.index',
            ArticleType::Leaderboard => 'leaderboard.comment.index',
            default => abort(404),
        };

        $routeParam = match ($comment->ArticleType) {
            ArticleType::Game => ['game' => $commentable, 'page' => $page],
            ArticleType::Achievement => ['achievement' => $commentable, 'page' => $page],
            ArticleType::User => ['user' => $commentable, 'page' => $page],
            ArticleType::Leaderboard => ['leaderboard' => $commentable, 'page' => $page],
            default => abort(404),
        };

        return route($routeName, $routeParam) . $hashAnchor;
    }

    private function calculateCommentPage(
        Comment $comment,
        Game|Achievement|User|Leaderboard $commentable,
    ): int {
        /** @var ?User $currentUser */
        $currentUser = Auth::user();

        // Comments are displayed in ascending order by Submitted (oldest first).
        // Count how many comments come before this one.
        $positionFromStart = $commentable->visibleComments($currentUser)
            ->where('Submitted', '<', $comment->Submitted)
            ->count();

        // Position is 0-indexed, so add 1 for the comment itself.
        return (int) ceil(($positionFromStart + 1) / self::FULL_PAGE_COMMENTS_PER_PAGE);
    }
}
