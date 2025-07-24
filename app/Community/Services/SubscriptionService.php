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
