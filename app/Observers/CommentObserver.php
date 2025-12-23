<?php

declare(strict_types=1);

namespace App\Observers;

use App\Community\Enums\ArticleType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Models\Comment;
use App\Models\UserDelayedSubscription;

class CommentObserver
{
    public function deleting(Comment $comment): void
    {
        /**
         * When a comment is deleted, update any UserDelayedSubscription entities
         * that reference it as their `first_update_id`. This ensures the daily
         * digest email links point to valid comments.
         */
        $this->updateAffectedDelayedSubscriptions($comment);
    }

    private function updateAffectedDelayedSubscriptions(Comment $comment): void
    {
        $affectedDelayedSubscriptions = UserDelayedSubscription::query()
            ->where('first_update_id', $comment->ID)
            ->get();

        foreach ($affectedDelayedSubscriptions as $subscription) {
            $articleType = $this->mapSubscriptionTypeToArticleType($subscription->subject_type);
            if ($articleType === null) {
                continue;
            }

            // Find the next available comment for this subscription.
            $nextComment = Comment::query()
                ->where('ArticleType', $articleType)
                ->where('ArticleID', $subscription->subject_id)
                ->where('ID', '>', $comment->ID)
                ->where('user_id', '!=', $subscription->user_id)
                ->orderBy('ID')
                ->first();

            if ($nextComment) {
                $subscription->update(['first_update_id' => $nextComment->ID]);
            } else {
                $subscription->delete();
            }
        }
    }

    private function mapSubscriptionTypeToArticleType(SubscriptionSubjectType $type): ?int
    {
        return match ($type) {
            SubscriptionSubjectType::Achievement => ArticleType::Achievement,
            SubscriptionSubjectType::GameWall => ArticleType::Game,
            SubscriptionSubjectType::UserWall => ArticleType::User,
            SubscriptionSubjectType::Leaderboard => ArticleType::Leaderboard,
            SubscriptionSubjectType::AchievementTicket => ArticleType::AchievementTicket,
            default => null,
        };
    }
}
