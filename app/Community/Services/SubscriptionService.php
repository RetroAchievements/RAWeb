<?php

declare(strict_types=1);

namespace App\Community\Services;

use App\Community\Enums\ArticleType;
use App\Community\Enums\SubscriptionSubjectType;
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

        $implicitSubscriptionsQuery = $this->getImplicitSubscriptionsQuery($subjectType, $subjectId)
            ->whereNotIn('user_id', $subscribers->pluck('user_id'));

        // only keep explicitly subscribed
        $subscribers = $subscribers->filter(fn ($s) => $s->state === true);

        // merge in implicit subscriptions
        foreach ($implicitSubscriptionsQuery->get() as $implicitSubscription) {
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

    // Get a single Subscription object for a user tied to a specific article.
    // Returns a new object if an explicit subscription is not found so the caller
    // can update the state and commit if desired.
    // NOTE: on commit of Subscription, getSubscriptionCounts needs to be expired.
    public function getSubscription(User $user, SubscriptionSubjectType $subjectType, int $subjectId): Subscription
    {
        $subscription = Subscription::query()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('user_id', $user->id)
            ->first();

        if (!$subscription) {
            $implicitlySubscribed = $this->getImplicitSubscriptionsQuery($subjectType, $subjectId)
                ->where('user_id', $user->id)->exists() ? 1 : 0; 

            $subscription = new Subscription([
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'user_id' => $user->id,
                'state' => $implicitlySubscribed,
            ]);
        }

        return $subscription;
    }

    private function getImplicitSubscriptionsQuery(SubscriptionSubjectType $subjectType, ?int $subjectId): Builder
    {
      return match($subjectType) {
         SubscriptionSubjectType::GameWall => $this->getImplicitCommentSubscriptionQuery(ArticleType::Game, $subjectId),
      };
    }

    private function getImplicitCommentSubscriptionQuery(int $articleType, ?int $articleId): Builder
    {
        $query = Comment::where('ArticleType', $articleType);
      
        if ($articleId !== null)
            $query->where('ArticleId', $articleId);
        
        $query->select(['user_id', 'ArticleId as subject_id'])->distinct();

        return $query;
    }
}
