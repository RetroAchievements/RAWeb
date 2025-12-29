<?php

declare(strict_types=1);

namespace App\Community\Enums;

use Spatie\TypeScriptTransformer\Attributes\TypeScript;

// TODO eventually use commentable morph

#[TypeScript]
enum CommentableType: string
{
    case Achievement = 'achievement.comment';
    case AchievementTicket = 'trigger.ticket.comment';
    case Forum = 'forum-topic.comment'; // Forum topic comments (posts) use ForumTopicComment table, not the comments table. This is for email notifications only.
    case Game = 'game.comment';
    case GameHash = 'game-hash.comment';
    case GameModification = 'game-modification.comment';
    case Leaderboard = 'leaderboard.comment';
    case SetClaim = 'achievement-set-claim.comment';
    case User = 'user.comment';
    case UserActivity = 'user-activity.comment';
    case UserModeration = 'user-moderation.comment';

    public function label(): string
    {
        return match ($this) {
            self::Achievement => 'Achievement',
            self::AchievementTicket => 'Achievement Ticket',
            self::Forum => 'Forum',
            self::Game => 'Game',
            self::GameHash => 'Game Hash',
            self::GameModification => 'Game Modification',
            self::Leaderboard => 'Leaderboard',
            self::SetClaim => 'Set Claim',
            self::User => 'User',
            self::UserActivity => 'User Activity',
            self::UserModeration => 'User Moderation',
        };
    }

    /**
     * Returns the legacy integer value for V1 API backwards compatibility.
     * These values were used when ArticleType was an integer-backed enum
     * and must remain stable for existing API consumers.
     */
    public function toLegacyInteger(): int
    {
        return match ($this) {
            self::Game => 1,
            self::Achievement => 2,
            self::User => 3,
            self::UserActivity => 5,
            self::Leaderboard => 6,
            self::AchievementTicket => 7,
            self::Forum => 8,
            self::UserModeration => 9,
            self::GameHash => 10,
            self::SetClaim => 11,
            self::GameModification => 12,
        };
    }

    public static function fromLegacyInteger(int $value): ?self
    {
        return match ($value) {
            1 => self::Game,
            2 => self::Achievement,
            3 => self::User,
            5 => self::UserActivity,
            6 => self::Leaderboard,
            7 => self::AchievementTicket,
            8 => self::Forum,
            9 => self::UserModeration,
            10 => self::GameHash,
            11 => self::SetClaim,
            12 => self::GameModification,
            default => null,
        };
    }

    /**
     * Check if this commentable type supports the intelligent comment redirect route.
     * These types can use route('comment.show') for proper pagination handling.
     */
    public function supportsCommentRedirect(): bool
    {
        return in_array($this, [
            self::Achievement,
            self::Game,
            self::Leaderboard,
            self::User,
        ], true);
    }
}
