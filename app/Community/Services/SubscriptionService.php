<?php

declare(strict_types=1);

namespace App\Community\Services;

use App\Community\Enums\ArticleType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\ForumTopicComment;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * @return Collection<int, User>
     */
    public function getSubscribers(SubscriptionSubjectType $subjectType, int $subjectId): Collection
    {
        // get explicit subscriptions (including explicitly unsubscribed so we don't fetch their implicit subscription)
        $subscribers = Subscription::query()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->get();
        $explicitSubcriberIds = $subscribers->pluck('user_id')->toArray();

        $implicitSubscriptionsQuery = $this->getImplicitSubscriptionsQuery($subjectType, $subjectId, ignoreUserIds: $explicitSubcriberIds);

        // discard explicitly unsubscribed
        $subscriberIds = $subscribers->filter(fn ($s) => $s->state === true)->pluck('user_id')->toArray();

        // merge in implicit subscriptions
        $implicitSubscriberIds = $implicitSubscriptionsQuery->get()->pluck('user_id')->toArray();
        $subscriberIds = array_merge($subscriberIds, $implicitSubscriberIds);

        return User::whereIn('ID', $subscriberIds)->get();
    }

    public function isSubscribed(User $user, SubscriptionSubjectType $subjectType, int $subjectId): bool
    {
        $subscription = Subscription::query()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->where('user_id', $user->id)
            ->first();

        if ($subscription) {
            // found explicit subscription for user
            return $subscription->state;
        }

        return $this->getImplicitSubscriptionsQuery($subjectType, $subjectId, forUserId: $user->id)
            ->exists();
    }

    public function updateSubscription(User $user, SubscriptionSubjectType $subjectType, int $subjectId, bool $isSubscribed): Subscription
    {
        return Subscription::updateOrCreate([
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'user_id' => $user->id,
        ], [
            'state' => $isSubscribed,
        ]);
    }

    /**
     * @param  int $forUserId         if not null, we're trying to decide if the specified user has an implicit subscription
     * @param  array $ignoreUserIds   if not null, we don't want implicit subscription information for the specified users
     * @return Builder<Model>
     */
    private function getImplicitSubscriptionsQuery(SubscriptionSubjectType $subjectType, ?int $subjectId, ?int $forUserId = null, ?array $ignoreUserIds = null): Builder
    {
        return match ($subjectType) {
            SubscriptionSubjectType::Achievement => $this->getImplicitAchievementCommentSubscriptionQuery($subjectId, $forUserId, $ignoreUserIds),
            SubscriptionSubjectType::AchievementTicket => $this->getImplicitTicketSubscriptionQuery($subjectId, $forUserId, $ignoreUserIds),
            SubscriptionSubjectType::ForumTopic => $this->getImplicitForumTopicSubscriptionQuery($subjectId, $forUserId, $ignoreUserIds),
            SubscriptionSubjectType::GameAchievements => $this->getNoImplicitSubscriptionQuery(),
            SubscriptionSubjectType::GameTickets => $this->getNoImplicitSubscriptionQuery(),
            SubscriptionSubjectType::GameWall => $this->getImplicitCommentSubscriptionQuery(ArticleType::Game, $subjectId, $forUserId, $ignoreUserIds),
            SubscriptionSubjectType::Leaderboard => $this->getImplicitCommentSubscriptionQuery(ArticleType::Leaderboard, $subjectId, $forUserId, $ignoreUserIds),
            SubscriptionSubjectType::UserWall => $this->getImplicitCommentSubscriptionQuery(ArticleType::User, $subjectId, $forUserId, $ignoreUserIds),
        };
    }

    /**
     * @return Builder<Model>
     */
    private function getNoImplicitSubscriptionQuery(): Builder
    {
        /** @var Builder<Model> $query */
        $query = Subscription::whereRaw('1 = 0');

        return $query;
    }

    /**
     * @return Builder<Model>
     */
    private function getImplicitCommentSubscriptionQuery(int $articleType, ?int $articleId, ?int $forUserId, ?array $ignoreUserIds): Builder
    {
        /** @var Builder<Model> $query */
        $query = Comment::where('ArticleType', $articleType)
            ->where('user_id', '!=', Comment::SYSTEM_USER_ID);

        if ($articleId !== null) {
            $query->where('ArticleId', $articleId);
        }

        if ($forUserId !== null) {
            $query->where('user_id', $forUserId);
        } elseif ($ignoreUserIds !== null) {
            $query->whereNotIn('user_id', $ignoreUserIds);
        }

        $query->select(['user_id', DB::raw('ArticleId as subject_id')])->distinct();

        return $query;
    }

    /**
     * @return Builder<Model>
     */
    private function getImplicitAchievementCommentSubscriptionQuery(?int $articleId, ?int $forUserId, ?array $ignoreUserIds): Builder
    {
        // find any users who have commented on the achievement
        $query = $this->getImplicitCommentSubscriptionQuery(ArticleType::Achievement, $articleId, $forUserId, $ignoreUserIds);

        if ($articleId !== null) {
            // find any users subscribed to GameAchievements for the game owning the achievement
            $achievement = Achievement::find($articleId);
            if ($achievement) {
                /** @var Builder<Model> $query2 */
                $query2 = Subscription::query()
                    ->where('subject_type', SubscriptionSubjectType::GameAchievements)
                    ->where('subject_id', $achievement->GameID)
                    ->select(['user_id', 'subject_id']);

                if ($forUserId !== null) {
                    $query2->where('user_id', $forUserId);
                } elseif ($ignoreUserIds !== null) {
                    $query2->whereNotIn('user_id', $ignoreUserIds);
                }

                $query->union($query2);
            }
        }

        return $query;
    }

    /**
     * @return Builder<Model>
     */
    private function getImplicitTicketSubscriptionQuery(?int $articleId, ?int $forUserId, ?array $ignoreUserIds): Builder
    {
        // find any users who have commented on the ticket
        $query = $this->getImplicitCommentSubscriptionQuery(ArticleType::AchievementTicket, $articleId, $forUserId, $ignoreUserIds);

        if ($articleId !== null) {
            $ticket = Ticket::with('achievement')->find($articleId);
            if ($ticket) {
                // find any users subscribed to GameTickets for the game owning the ticketed achievement
                /** @var Builder<Model> $query2 */
                $query2 = Subscription::query()
                    ->where('subject_type', SubscriptionSubjectType::GameTickets)
                    ->where('subject_id', $ticket->achievement->GameID)
                    ->select(['user_id', 'subject_id']);

                if ($forUserId !== null) {
                    $query2->where('user_id', $forUserId);
                } elseif ($ignoreUserIds !== null) {
                    $query2->whereNotIn('user_id', $ignoreUserIds);
                }

                $query->union($query2);

                // reporter should also be implicitly subscribed
                $includeReporter = false;
                if ($forUserId !== null) {
                    $includeReporter = $ticket->reporter_id === $forUserId;
                } else {
                    $includeReporter = !$ignoreUserIds || !in_array($ticket->reporter_id, $ignoreUserIds);
                }

                if ($includeReporter) {
                    /** @var Builder<Model> $query3 */
                    $query3 = Ticket::query()
                        ->select([
                            DB::raw('reporter_id as user_id'),
                            DB::raw('ID as subject_id'),
                        ])
                        ->where('ID', $ticket->ID);

                    $query->union($query3);
                }

                // achievement maintainer should also be implicitly subscribed if they still have a development role
                $includeMaintainer = false;
                $maintainer = $ticket->achievement->getMaintainerAt(now());
                if ($maintainer && $maintainer->hasAnyRole([Role::DEVELOPER, Role::DEVELOPER_JUNIOR])) {
                    if ($forUserId !== null) {
                        $includeMaintainer = $maintainer->id === $forUserId;
                    } else {
                        $includeMaintainer = !$ignoreUserIds || !in_array($maintainer->id, $ignoreUserIds);
                    }
                }

                if ($includeMaintainer) {
                    /** @var Builder<Model> $query4 */
                    $query4 = Ticket::query()
                        ->select([
                            DB::raw($maintainer->id . ' as user_id'),
                            DB::raw('ID as subject_id'),
                        ])
                        ->where('ID', $ticket->ID);

                    $query->union($query4);
                }
            }
        }

        return $query;
    }

    /**
     * @return Builder<Model>
     */
    private function getImplicitForumTopicSubscriptionQuery(?int $forumTopicId, ?int $forUserId, ?array $ignoreUserIds): Builder
    {
        /** @var Builder<Model> $query */
        $query = ForumTopicComment::query();

        if ($forumTopicId !== null) {
            $query->where('forum_topic_id', $forumTopicId);
        }

        if ($forUserId !== null) {
            $query->where('author_id', $forUserId);
        } elseif ($ignoreUserIds !== null) {
            $query->whereNotIn('author_id', $ignoreUserIds);
        }

        $query->select([
            DB::raw('author_id as user_id'),
            DB::raw('forum_topic_id as subject_id'),
        ])->distinct();

        return $query;
    }
}
