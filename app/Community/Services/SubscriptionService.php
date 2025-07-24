<?php

declare(strict_types=1);

namespace App\Community\Services;

use App\Community\Enums\ArticleType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * @return Collection<int, Subscription>
     */
    public function getSubscribers(SubscriptionSubjectType $subjectType, int $subjectId): Collection
    {
        // get explicit subscriptions (including explicitly unsubscribed so we don't fetch their implicit subscription)
        $subscribers = Subscription::query()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->get();
        $explicitSubcriberIds = $subscribers->pluck('user_id')->toArray();

        $implicitSubscriptionsQuery = $this->getImplicitSubscriptionsQuery($subjectType, $subjectId)
            ->whereNotIn('user_id', $explicitSubcriberIds);

        // only keep explicitly subscribed
        $subscribers = $subscribers->filter(fn ($s) => $s->state === true);

        // merge in implicit subscriptions
        foreach ($implicitSubscriptionsQuery->get() as $implicitSubscription) {
            // if the implicit subscription query contains a union, the whereNotIn is not
            // applied to the unioned subqueries, so add a sanity check to filter out
            // explicit subscribers.
            if (in_array($implicitSubscription->user_id, $explicitSubcriberIds)) {
                continue;
            }

            $subscription = new Subscription([
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'user_id' => $implicitSubscription->user_id,
                'state' => true,
            ]);
            $subscribers->add($subscription);
        }

        $subscribers->loadMissing('user');

        return $subscribers;
    }

    public function isSubscribed(User $user, SubscriptionSubjectType $subjectType, int $subjectId): bool
    {
        $subscription = Subscription::query()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('user_id', $user->id)
            ->first();

        if ($subscription) {
            return $subscription->state;
        }

        return $this->getImplicitSubscriptionsQuery($subjectType, $subjectId)
            ->where('user_id', $user->id)
            ->exists();
    }

    public function updateSubscription(User $user, SubscriptionSubjectType $subjectType, int $subjectId, bool $isSubscribed): void
    {
        Subscription::updateOrCreate([
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'user_id' => $user->id,
        ],[
            'state' => $isSubscribed,
        ]);
    }

    private function getImplicitSubscriptionsQuery(SubscriptionSubjectType $subjectType, ?int $subjectId): Builder
    {
        return match($subjectType) {
            SubscriptionSubjectType::Achievement => $this->getImplicitAchievementCommentSubscriptionQuery($subjectId),
            SubscriptionSubjectType::GameAchievements => $this->getNoImplicitSubscriptionQuery(),
            SubscriptionSubjectType::GameTickets => $this->getNoImplicitSubscriptionQuery(),
            SubscriptionSubjectType::GameWall => $this->getImplicitCommentSubscriptionQuery(ArticleType::Game, $subjectId),
            SubscriptionSubjectType::UserWall => $this->getImplicitCommentSubscriptionQuery(ArticleType::User, $subjectId),
        };
    }

    private function getNoImplicitSubscriptionQuery(): Builder
    {
        return Subscription::whereRaw('1 = 0');
    }

    private function getImplicitCommentSubscriptionQuery(int $articleType, ?int $articleId): Builder
    {
        $query = Comment::where('ArticleType', $articleType)
            ->where('user_id', '!=', Comment::SYSTEM_USER_ID);
      
        if ($articleId !== null)
            $query->where('ArticleId', $articleId);
        
        $query->select(['user_id', 'ArticleId as subject_id'])->distinct();

        return $query;
    }

    private function getImplicitAchievementCommentSubscriptionQuery(?int $articleId): Builder
    {
        // find any users who have commented on the achievement
        $query = $this->getImplicitCommentSubscriptionQuery(ArticleType::Achievement, $articleId);

        if ($articleId !== null) {
            // find any users subscribed to GameAchievements for the game owning the achievement
            $achievement = Achievement::find($articleId);
            if ($achievement) {
                $query2 = Subscription::query()
                    ->where('subject_type', SubscriptionSubjectType::GameAchievements)
                    ->where('subject_id', $achievement->GameID)
                    ->select(['user_id', 'subject_id']);

                $query->union($query2)->distinct();
            }
        }

        return $query;
    }
}
