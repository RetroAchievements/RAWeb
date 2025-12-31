<?php

declare(strict_types=1);

namespace App\Community\Services;

use App\Community\Enums\ArticleType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    /**
     * Gets all users who are explicitly or implicitly subscribed to the subject.
     *
     * @return Collection<int, User>
     */
    public function getSubscribers(SubscriptionSubjectType $subjectType, int $subjectId): Collection
    {
        [$explicitSubscriberIds, $implicitSubscriberIds] = $this->getSubscriberIds($subjectType, $subjectId);

        $allSubscriberIds = array_merge($explicitSubscriberIds, $implicitSubscriberIds);

        return User::whereIn('ID', $allSubscriberIds)->get();
    }

    /**
     * Gets all users who are explicitly or implicitly subscribed to the subject separated into three categories.
     *
     * @param SubscriptionSubjectType $subjectType the type of object to get subscribers of
     * @param int $subjectId the unique identifier of the object to get subscribers of
     * @param ?int $subjectAuthorUserId Optionally specifies the user who originally created the object.
     *                                  Ensures they get returned in the `implicitlySubscribedNotifyNow` bucket if they are only implicitly subscribed.
     *
     * @return array containing the following:
     *   'explicitlySubscribed': Users who are explicitly subscribed to the content.
     *   'implicitlySubscribedNotifyNow': Users who are implicitly subscribed to the content and should be notified immediately.
     *   'implicitlySubscribedNotifyLater': Users who are implicitly subscribed to the content and should be notified when convienent.
     */
    public function getSegmentedSubscriberIds(SubscriptionSubjectType $subjectType, int $subjectId, ?int $subjectAuthorUserId): array
    {
        [$explicitSubscriberIds, $implicitSubscriberIds] = $this->getSubscriberIds($subjectType, $subjectId);

        // split implicit subscriptions into recent and older
        $recentActivityCutoff = now()->subDays(7);
        $handler = $this->getHandler($subjectType);
        $recentUserIds = $handler->getRecentParticipants($subjectId, $recentActivityCutoff);

        if ($subjectAuthorUserId) {
            // if the author is implicitly subscribed, ensure they get classified as "notify now"
            $recentUserIds[] = $subjectAuthorUserId;
        }

        return [
            'explicitlySubscribed' => $explicitSubscriberIds,
            'implicitlySubscribedNotifyNow' => array_filter($implicitSubscriberIds, fn ($id) => in_array($id, $recentUserIds)),
            'implicitlySubscribedNotifyLater' => array_filter($implicitSubscriberIds, fn ($id) => !in_array($id, $recentUserIds)),
        ];
    }

    private function getSubscriberIds(SubscriptionSubjectType $subjectType, int $subjectId): array
    {
        // get explicit subscriptions (including explicitly unsubscribed so we don't fetch their implicit subscription)
        $subscribers = Subscription::query()
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->get();
        $explicitSubscriberIds = $subscribers->pluck('user_id')->toArray();

        // get implicit subscriptions
        $handler = $this->getHandler($subjectType);
        $implicitSubscriptionsQuery = $handler->getImplicitSubscriptionQuery($subjectId, null, null, $explicitSubscriberIds);
        $implicitSubscriberIds = $implicitSubscriptionsQuery->get()->pluck('user_id')->toArray();

        // discard explicitly unsubscribed
        $explicitSubscriberIds = $subscribers->filter(fn ($s) => $s->state === true)->pluck('user_id')->toArray();

        return [$explicitSubscriberIds, $implicitSubscriberIds];
    }

    /**
     * Determines if the user is subscribed to the subject (either implicitly or explicity).
     */
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

        $handler = $this->getHandler($subjectType);

        return $handler->getImplicitSubscriptionQuery($subjectId, $user->id, null, null)->exists();
    }

    /**
     * Sets the explicit subscription state of a subject for a user.
     */
    public function updateSubscription(User $user, SubscriptionSubjectType $subjectType, int $subjectId, bool $isSubscribed): Subscription
    {
        $subscription = Subscription::updateOrCreate([
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'user_id' => $user->id,
        ], [
            'state' => $isSubscribed,
        ]);

        Cache::forget("users.{$user->id}.subscriptions.{$subjectType->value}");

        return $subscription;
    }

    /**
     * Gets the number of subscriptions (explicit or implicit) a user has.
     */
    public function getSubscriptionCount(User $user, array $subjectTypes): int
    {
        $count = 0;

        foreach ($subjectTypes as $subjectType) {
            $count += count($this->getSubscriptionSubjectIds($user, $subjectType));
        }

        return $count;
    }

    /**
     * Gets all of the subscriptions (explicit or implicit) a user has.
     *
     * Use "subscription->exists()" to detect implicit subscriptions.
     *
     * @return Collection<int, Subscription>
     */
    public function getSubscriptions(User $user, array $subjectTypes, int $offset = 0, int $count = 100): Collection
    {
        $results = new Collection();

        foreach ($subjectTypes as $subjectType) {
            $subscriptionIds = $this->getSubscriptionSubjectIds($user, $subjectType);
            $subscriptionCount = count($subscriptionIds);

            if ($offset < $subscriptionCount) {
                $subscriptions = $this->getSubjectSubscriptions($user, $subjectType, $subscriptionIds, $offset, $count);
                $results = $results->merge($subscriptions);

                $count -= ($subscriptionCount - $offset);
                if ($count <= 0) {
                    break;
                }

                $offset = 0;
            } else {
                $offset -= $subscriptionCount;
            }
        }

        return $results;
    }

    /**
     * @return Collection<int, Subscription>
     */
    private function getSubjectSubscriptions(User $user, SubscriptionSubjectType $subjectType, array $subjectIds, int $offset, int $count): Collection
    {
        $handler = $this->getHandler($subjectType);
        $subjects = $handler->getSubjectQuery($subjectIds)
            ->toBase() // tell Eloquent not to bind to whatever model was used to construct the query
            ->offset($offset)
            ->limit($count)
            ->get();
        $resultIds = $subjects->pluck('subject_id')->toArray();

        $explicitSubscriptions = Subscription::query()
            ->where('user_id', $user->id)
            ->where('subject_type', $subjectType)
            ->whereIn('subject_id', $resultIds)
            ->get()
            ->mapWithKeys(fn ($item) => [$item['subject_id'] => $item]);

        $results = new Collection();
        foreach ($subjects as $row) {
            if ($explicitSubscriptions->has($row->subject_id)) {
                $result = $explicitSubscriptions[$row->subject_id];
            } else {
                $result = new Subscription([
                    'user_id' => $user->id,
                    'subject_type' => $subjectType,
                    'subject_id' => $row->subject_id,
                    'state' => true,
                ]);
            }

            $result->title = $row->title;

            $results->push($result);
        }

        return $results;
    }

    private function getSubscriptionSubjectIds(User $user, SubscriptionSubjectType $subjectType): array
    {
        $ttl = 12 * 60 * 60; // 12 hours

        return Cache::remember("users.{$user->id}.subscriptions.{$subjectType->value}", $ttl, function () use ($user, $subjectType) {
            $handler = $this->getHandler($subjectType);
            if (!$handler->includeImplicitSubscriptionsInList()) {
                return Subscription::query()
                    ->where('user_id', $user->id)
                    ->where('subject_type', $subjectType)
                    ->where('state', true)
                    ->select('subject_id')
                    ->get()
                    ->toArray();
            }

            // get explicit subscriptions (including explicitly unsubscribed so we don't fetch their implicit subscription)
            $subscriptions = Subscription::query()
                ->where('user_id', $user->id)
                ->where('subject_type', $subjectType)
                ->get();

            $explicitSubscriptionIds = $subscriptions->filter(fn ($s) => $s->state === true)->pluck('subject_id')->toArray();
            $allSubscriptionIds = $subscriptions->pluck('subject_id')->toArray();

            $implicitSubscriptionIds = $handler->getImplicitSubscriptionQuery(null, $user->id, $allSubscriptionIds, null)
                ->get()
                ->pluck('subject_id')
                ->toArray();

            return array_merge($explicitSubscriptionIds, $implicitSubscriptionIds);
       });
    }

    private function getHandler(SubscriptionSubjectType $subjectType): BaseSubscriptionHandler
    {
        return match ($subjectType) {
            SubscriptionSubjectType::Achievement => new AchievementWallSubscriptionHandler(),
            SubscriptionSubjectType::AchievementTicket => new AchievementTicketSubscriptionHandler(),
            SubscriptionSubjectType::ForumTopic => new ForumTopicSubscriptionHandler(),
            SubscriptionSubjectType::GameAchievements => new GameAchievementsSubscriptionHandler(),
            SubscriptionSubjectType::GameTickets => new GameTicketsSubscriptionHandler(),
            SubscriptionSubjectType::GameWall => new GameWallSubscriptionHandler(),
            SubscriptionSubjectType::Leaderboard => new LeaderboardWallSubscriptionHandler(),
            SubscriptionSubjectType::UserWall => new UserWallSubscriptionHandler(),
        };
    }
}

abstract class BaseSubscriptionHandler
{
    /**
     * Builds a query that returns 'subject_id' and 'title' for all records matching the requested subjectIds
     *
     * @return Builder<Model>
     */
    abstract public function getSubjectQuery(array $subjectIds): Builder;

    /**
     * Builds a query that returns the 'user_id' and 'subject_id' for implicilty subscribed-to records matching the requested filters
     *
     * @param  ?int $subjectId            if not null, we're trying to decide who is implicitly subscribed to the item
     * @param  ?int $forUserId            if not null, we're trying to decide if the specified user has an implicit subscription
     * @param  ?array $ignoreSubjectIds   if not null, we don't want implicit subscription information for the specified subjects
     * @param  ?array $ignoreUserIds      if not null, we don't want implicit subscription information for the specified users
     * @return Builder<Model>
     */
    public function getImplicitSubscriptionQuery(?int $subjectId, ?int $forUserId, ?array $ignoreSubjectIds, ?array $ignoreUserIds): Builder
    {
        /** @var Builder<Model> $query */
        $query = Subscription::whereRaw('1 = 0');

        return $query;
    }

    /**
     * Determines if implicit subscriptions should be returned when getting all subscriptions for a user.
     *
     * i.e. a user probably doesn't care about which tickets they're implicitly subscribed to.
     */
    public function includeImplicitSubscriptionsInList(): bool
    {
        return true;
    }

    /**
     * Returns IDs of users who have contributed to the subject since the provided time.
     */
    abstract public function getRecentParticipants(int $subjectId, Carbon $since): array;
}

abstract class CommentSubscriptionHandler extends BaseSubscriptionHandler
{
    abstract protected function getArticleType(): int;

    /**
     * @return Builder<Model>
     */
    public function getImplicitSubscriptionQuery(?int $subjectId, ?int $forUserId, ?array $ignoreSubjectIds, ?array $ignoreUserIds): Builder
    {
        /** @var Builder<Model> $query */
        $query = Comment::where('ArticleType', $this->getArticleType());

        if ($subjectId !== null) {
            $query->where('ArticleId', $subjectId);
        } elseif (!empty($ignoreSubjectIds)) {
            $query->whereNotIn('ArticleId', $ignoreSubjectIds);
        }

        if ($forUserId !== null) {
            $query->where('user_id', $forUserId);
        } else {
            $query->where('user_id', '!=', Comment::SYSTEM_USER_ID);

            if ($ignoreUserIds !== null) {
                $query->whereNotIn('user_id', $ignoreUserIds);
            }
        }

        $query->select(['user_id', DB::raw('ArticleId as subject_id')])->distinct();

        return $query;
    }

    public function getRecentParticipants(int $subjectId, Carbon $since): array
    {
        return Comment::query()
            ->where('ArticleType', $this->getArticleType())
            ->where('ArticleId', $subjectId)
            ->where('Submitted', '>=', $since)
            ->distinct()
            ->pluck('user_id')
            ->toArray();
    }
}

class AchievementWallSubscriptionHandler extends CommentSubscriptionHandler
{
    protected function getArticleType(): int
    {
        return ArticleType::Achievement;
    }

    /**
     * @return Builder<Model>
     */
    public function getSubjectQuery(array $subjectIds): Builder
    {
        /** @var Builder<Model> $query */
        $query = Achievement::whereIn('ID', $subjectIds)
            ->select([
                DB::raw('ID as subject_id'),
                DB::raw('Title as title'),
            ])
            ->orderBy('title');

        return $query;
    }

    /**
     * @return Builder<Model>
     */
    public function getImplicitSubscriptionQuery(?int $subjectId, ?int $forUserId, ?array $ignoreSubjectIds, ?array $ignoreUserIds): Builder
    {
        // find any users who have commented on the achievement
        $query = parent::getImplicitSubscriptionQuery($subjectId, $forUserId, $ignoreSubjectIds, $ignoreUserIds);

        if ($subjectId !== null) {
            // find any users subscribed to GameAchievements for the game owning the achievement
            $achievement = Achievement::find($subjectId);
            if ($achievement) {
                /** @var Builder<Model> $query2 */
                $query2 = Subscription::query()
                    ->where('subject_type', SubscriptionSubjectType::GameAchievements)
                    ->where('subject_id', $achievement->GameID)
                    ->where('state', true)
                    ->select(['user_id', 'subject_id']);

                if ($forUserId !== null) {
                    $query2->where('user_id', $forUserId);
                } elseif ($ignoreUserIds !== null) {
                    $query2->whereNotIn('user_id', $ignoreUserIds);
                }

                $query->union($query2);

                // achievement maintainer should also be implicitly subscribed if they still have a development role
                $includeMaintainer = false;
                $maintainer = $achievement->getMaintainerAt(now());
                if ($maintainer && $maintainer->hasAnyRole([Role::DEVELOPER, Role::DEVELOPER_JUNIOR])) {
                    if ($forUserId !== null) {
                        $includeMaintainer = $maintainer->id === $forUserId;
                    } else {
                        $includeMaintainer = !$ignoreUserIds || !in_array($maintainer->id, $ignoreUserIds);
                    }
                }

                if ($includeMaintainer) {
                    /** @var Builder<Model> $query3 */
                    $query3 = Achievement::query()
                        ->where('ID', $achievement->ID)
                        ->select([
                            DB::raw($maintainer->id . ' as user_id'),
                            DB::raw('ID as subject_id'),
                        ]);

                    $query->union($query3);
                }
            }
        } elseif ($forUserId !== null) {
            // If a user is subscribed to GameAchievements for a game, they're implicitly subscribed to all achievements in the game.
            // Don't return those achievements individually. Make the user manage the subscription at the game level.
        }

        return $query;
    }
}

class AchievementTicketSubscriptionHandler extends CommentSubscriptionHandler
{
    protected function getArticleType(): int
    {
        return ArticleType::AchievementTicket;
    }

    /**
     * @return Builder<Model>
     */
    public function getSubjectQuery(array $subjectIds): Builder
    {
        /** @var Builder<Model> $query */
        $query = Ticket::whereIn('Ticket.ID', $subjectIds)
            ->join('Achievements', 'Ticket.AchievementID', '=', 'Achievements.ID')
            ->select([
                DB::raw('Ticket.ID as subject_id'),
                DB::raw('Achievements.Title as title'),
            ])
            ->orderBy('title');

        return $query;
    }

    /**
     * @return Builder<Model>
     */
    public function getImplicitSubscriptionQuery(?int $subjectId, ?int $forUserId, ?array $ignoreSubjectIds, ?array $ignoreUserIds): Builder
    {
        // find any users who have commented on the ticket
        $query = parent::getImplicitSubscriptionQuery($subjectId, $forUserId, $ignoreSubjectIds, $ignoreUserIds);

        if ($subjectId !== null) {
            $ticket = Ticket::with('achievement')->find($subjectId);
            if ($ticket) {
                // find any users subscribed to GameTickets for the game owning the ticketed achievement
                /** @var Builder<Model> $query2 */
                $query2 = Subscription::query()
                    ->where('subject_type', SubscriptionSubjectType::GameTickets)
                    ->where('subject_id', $ticket->achievement->GameID)
                    ->where('state', true)
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
                        ->where('ID', $ticket->ID)
                        ->select([
                            DB::raw('reporter_id as user_id'),
                            DB::raw('ID as subject_id'),
                        ]);

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
                        ->where('ID', $ticket->ID)
                        ->select([
                            DB::raw($maintainer->id . ' as user_id'),
                            DB::raw('ID as subject_id'),
                        ]);

                    $query->union($query4);
                }
            }
        } elseif ($forUserId !== null) {
            // If a user is subscribed to GameTickets for a game, they're implicitly subscribed to all tickets for the game.
            // Don't return those tickets individually. Make the user manage the subscription at the game level.

            // find any tickets created by the user
            /** @var Builder<Model> $query3 */
            $query3 = Ticket::query()
                ->where('reporter_id', $forUserId)
                ->select([
                    DB::raw('reporter_id as user_id'),
                    DB::raw('ID as subject_id'),
                ]);
            if (!empty($ignoreSubjectIds)) {
                $query3->whereNotIn('ID', $ignoreSubjectIds);
            }
            $query->union($query3);
        }

        return $query;
    }
}

class ForumTopicSubscriptionHandler extends BaseSubscriptionHandler
{
    /**
     * @return Builder<Model>
     */
    public function getSubjectQuery(array $subjectIds): Builder
    {
        /** @var Builder<Model> $query */
        $query = ForumTopic::whereIn('ID', $subjectIds)
            ->select([
                DB::raw('id as subject_id'),
                'title',
            ])
            ->orderBy('title');

        return $query;
    }

    /**
     * @return Builder<Model>
     */
    public function getImplicitSubscriptionQuery(?int $subjectId, ?int $forUserId, ?array $ignoreSubjectIds, ?array $ignoreUserIds): Builder
    {
        /** @var Builder<Model> $query */
        $query = ForumTopicComment::query();

        if ($subjectId !== null) {
            $query->where('forum_topic_id', $subjectId);
        } elseif (!empty($ignoreSubjectIds)) {
            $query->whereNotIn('forum_topic_id', $ignoreSubjectIds);
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

    public function getRecentParticipants(int $subjectId, Carbon $since): array
    {
        return ForumTopicComment::query()
            ->where('forum_topic_id', $subjectId)
            ->where('created_at', '>=', $since)
            ->distinct()
            ->pluck('author_id')
            ->toArray();
    }
}

class GameAchievementsSubscriptionHandler extends BaseSubscriptionHandler
{
    /**
     * @return Builder<Model>
     */
    public function getSubjectQuery(array $subjectIds): Builder
    {
        /** @var Builder<Model> $query */
        $query = Game::whereIn('id', $subjectIds)
            ->select([
                DB::raw('id as subject_id'),
                DB::raw('title as title'),
            ])
            ->orderBy('sort_title');

        return $query;
    }

    public function includeImplicitSubscriptionsInList(): bool
    {
        return false;
    }

    public function getRecentParticipants(int $subjectId, Carbon $since): array
    {
        return [];
    }
}

class GameTicketsSubscriptionHandler extends BaseSubscriptionHandler
{
    /**
     * @return Builder<Model>
     */
    public function getSubjectQuery(array $subjectIds): Builder
    {
        /** @var Builder<Model> $query */
        $query = Game::whereIn('id', $subjectIds)
            ->select([
                DB::raw('id as subject_id'),
                DB::raw('title as title'),
            ])
            ->orderBy('sort_title');

        return $query;
    }

    public function includeImplicitSubscriptionsInList(): bool
    {
        return false;
    }

    public function getRecentParticipants(int $subjectId, Carbon $since): array
    {
        return [];
    }
}

class GameWallSubscriptionHandler extends CommentSubscriptionHandler
{
    protected function getArticleType(): int
    {
        return ArticleType::Game;
    }

    /**
     * @return Builder<Model>
     */
    public function getSubjectQuery(array $subjectIds): Builder
    {
        /** @var Builder<Model> $query */
        $query = Game::whereIn('id', $subjectIds)
            ->select([
                DB::raw('id as subject_id'),
                DB::raw('title as title'),
            ])
            ->orderBy('sort_title');

        return $query;
    }
}

class LeaderboardWallSubscriptionHandler extends CommentSubscriptionHandler
{
    protected function getArticleType(): int
    {
        return ArticleType::Leaderboard;
    }

    /**
     * @return Builder<Model>
     */
    public function getSubjectQuery(array $subjectIds): Builder
    {
        /** @var Builder<Model> $query */
        $query = Leaderboard::whereIn('ID', $subjectIds)
            ->select([
                DB::raw('ID as subject_id'),
                DB::raw('Title as title'),
            ])
            ->orderBy('title');

        return $query;
    }
}

class UserWallSubscriptionHandler extends CommentSubscriptionHandler
{
    protected function getArticleType(): int
    {
        return ArticleType::User;
    }

    /**
     * @return Builder<Model>
     */
    public function getSubjectQuery(array $subjectIds): Builder
    {
        /** @var Builder<Model> $query */
        $query = User::whereIn('ID', $subjectIds)
            ->select([
                DB::raw('ID as subject_id'),
                DB::raw('IFNULL(display_name, User) as title'),
            ])
            ->orderBy('title');

        return $query;
    }

    /**
     * @return Builder<Model>
     */
    public function getImplicitSubscriptionQuery(?int $subjectId, ?int $forUserId, ?array $ignoreSubjectIds, ?array $ignoreUserIds): Builder
    {
        // find any users who have commented on the user wall
        $query = parent::getImplicitSubscriptionQuery($subjectId, $forUserId, $ignoreSubjectIds, $ignoreUserIds);

        if ($subjectId !== null) {
            // wall owner is always implicitly subscribed
            $includeWallOwner = false;
            if ($forUserId !== null) {
                $includeWallOwner = $subjectId === $forUserId;
            } else {
                $includeWallOwner = !$ignoreUserIds || !in_array($subjectId, $ignoreUserIds);
            }

            if ($includeWallOwner) {
                /** @var Builder<Model> $query2 */
                $query2 = User::query()
                    ->where('ID', $subjectId)
                    ->select([
                        DB::raw('ID as user_id'),
                        DB::raw('ID as subject_id'),
                    ]);

                $query->union($query2);
            }
        }

        return $query;
    }
}
