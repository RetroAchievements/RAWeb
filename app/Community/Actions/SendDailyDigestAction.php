<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\CommentableType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Enums\UserPreference;
use App\Mail\DailyDigestMail;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserDelayedSubscription;
use App\Support\Shortcode\Shortcode;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class SendDailyDigestAction
{
    public function execute(User $user): void
    {
        // load up the pending subscriptions, then delete them.
        $delayedSubscriptions = UserDelayedSubscription::query()
            ->where('user_id', $user->id)
            ->orderBy('id')
            ->get();
        $last = $delayedSubscriptions->last();
        if (!$last) {
            return;
        }
        UserDelayedSubscription::query()
            ->where('user_id', $user->id)
            ->where('id', '<=', $last->id)
            ->delete();

        // if the user doesn't have an email address or is opted out, bail.
        // do this after deleting the pending subscriptions.
        if (!$user->EmailAddress || BitSet($user->websitePrefs, UserPreference::EmailOff_DailyDigest)) {
            return;
        }

        // build a map of ids for each type
        $ids = [];
        foreach ($delayedSubscriptions as $delayedSubscription) {
            $type = $delayedSubscription->subject_type->value;
            if (!isset($ids[$type])) {
                $ids[$type] = [];
            }
            $ids[$type][] = $delayedSubscription->subject_id;
        }
        // and preload the titles for the associated records
        $titles = [];
        foreach ($ids as $type => $typeIds) {
            $titles[$type] = match ($type) {
                SubscriptionSubjectType::ForumTopic->value => ForumTopic::whereIn('id', $typeIds)->pluck('title', 'id'),
                SubscriptionSubjectType::GameWall->value => $this->buildGameWallTitles($typeIds),
                SubscriptionSubjectType::Achievement->value => $this->buildAchievementWallTitles($typeIds),
                SubscriptionSubjectType::UserWall->value => User::whereIn('ID', $typeIds)->pluck('display_name', 'ID'),
                SubscriptionSubjectType::Leaderboard->value => $this->buildLeaderboardTitles($typeIds),
                SubscriptionSubjectType::AchievementTicket->value => $this->buildTicketTitles($typeIds),
                default => [],
            };
        }

        // build the data to pass to the mail script
        $singleItems = [];
        $notificationItems = [];
        foreach ($delayedSubscriptions as $delayedSubscription) {
            // if all the new posts have been deleted or aren't visible, ignore it
            $handler = $this->getHandler($delayedSubscription->subject_type);
            $count = $handler->getUpdatesSince($delayedSubscription);
            if ($count > 0) {
                $notificationItems[] = [
                    'type' => $delayedSubscription->subject_type->value,
                    'title' => $titles[$delayedSubscription->subject_type->value][$delayedSubscription->subject_id] ?? '(untitled)',
                    'link' => $handler->getLink($delayedSubscription->subject_id, $delayedSubscription->first_update_id),
                    'count' => $count,
                ];

                if ($count === 1) {
                    $singleItems[] = [$delayedSubscription, count($notificationItems) - 1];
                }
            }
        }

        if (empty($notificationItems)) {
            return;
        }

        if (!empty($singleItems)) {
            // build a map of ids for each type
            $ids = [];
            foreach ($singleItems as $singleItem) {
                $delayedSubscription = $singleItem[0];
                $type = $delayedSubscription->subject_type->value;
                if (!isset($ids[$type])) {
                    $ids[$type] = [];
                }
                $ids[$type][] = match ($type) {
                    SubscriptionSubjectType::ForumTopic->value => $delayedSubscription->subject_id,
                    default => $delayedSubscription->first_update_id,
                };
            }
            // load the posts for the associated records
            $posts = [];
            foreach ($ids as $type => $typeIds) {
                $posts[$type] = match ($type) {
                    SubscriptionSubjectType::ForumTopic->value => ForumTopic::whereIn('id', $typeIds)->with('latestComment.user')->get()->keyBy('id'),
                    default => Comment::whereIn('id', $typeIds)->with('user')->get()->keyBy('commentable_id'),
                };
            }

            // inject the summaries into the notification items
            foreach ($singleItems as $singleItem) {
                [$delayedSubscription, $index] = $singleItem;
                $type = $delayedSubscription->subject_type->value;
                $post = $posts[$type][$delayedSubscription->subject_id] ?? null;
                if ($post) {
                    $summary = match ($type) {
                        SubscriptionSubjectType::ForumTopic->value => Shortcode::stripAndClamp($post->latestComment->body, previewLength: 200, preserveWhitespace: true),
                        default => mb_strlen($post->body) > 200 ? mb_substr($post->body, 0, 200) . '...' : $post->body,
                    };
                    $displayName = match ($type) {
                        SubscriptionSubjectType::ForumTopic->value => $post->latestComment->user->display_name,
                        default => $post->user->display_name,
                    };

                    if ($summary && $displayName) {
                        $notificationItems[$index]['summary'] = $summary;
                        $notificationItems[$index]['author'] = $displayName;
                    }
                }
            }
        }

        // send the mail
        Mail::to($user->EmailAddress)->queue(
            new DailyDigestMail($user, $notificationItems)
        );
    }

    private function getHandler(SubscriptionSubjectType $subjectType): BaseDelayedSubscriptionHandler
    {
        return match ($subjectType) {
            SubscriptionSubjectType::ForumTopic => new ForumTopicDelayedSubscriptionHandler(),
            SubscriptionSubjectType::GameWall => new CommentDelayedSubscriptionHandler(CommentableType::Game),
            SubscriptionSubjectType::Achievement => new CommentDelayedSubscriptionHandler(CommentableType::Achievement),
            SubscriptionSubjectType::UserWall => new CommentDelayedSubscriptionHandler(CommentableType::User),
            SubscriptionSubjectType::Leaderboard => new CommentDelayedSubscriptionHandler(CommentableType::Leaderboard),
            SubscriptionSubjectType::AchievementTicket => new CommentDelayedSubscriptionHandler(CommentableType::AchievementTicket),

            // other cases will be filled in as the calls are updated.
            default => throw new InvalidArgumentException("No handler for {$subjectType->value}"),
        };
    }

    private function buildGameWallTitles(array $ids): array
    {
        $result = [];

        $games = Game::whereIn('ID', $ids)->with('system')->get();
        foreach ($games as $game) {
            $result[$game->ID] = "{$game->Title} ({$game->system->Name})";
        }

        return $result;
    }

    private function buildAchievementWallTitles(array $ids): array
    {
        $result = [];

        $achievements = Achievement::whereIn('ID', $ids)->with('game')->get();
        foreach ($achievements as $achievement) {
            $result[$achievement->ID] = "{$achievement->Title} ({$achievement->game->Title})";
        }

        return $result;
    }

    private function buildLeaderboardTitles(array $ids): array
    {
        $result = [];

        $leaderboards = Leaderboard::whereIn('ID', $ids)->with('game')->get();
        foreach ($leaderboards as $leaderboard) {
            $result[$leaderboard->ID] = "{$leaderboard->Title} ({$leaderboard->game->Title})";
        }

        return $result;
    }

    private function buildTicketTitles(array $ids): array
    {
        $result = [];

        $tickets = Ticket::whereIn('ID', $ids)->with('achievement')->get();
        foreach ($tickets as $ticket) {
            $result[$ticket->ID] = "{$ticket->achievement->Title}";
        }

        return $result;
    }
}

abstract class BaseDelayedSubscriptionHandler
{
    /**
     * Gets the number of updates that have been made by users other than the subscriber since the notification was delayed.
     */
    abstract public function getUpdatesSince(UserDelayedSubscription $delayedSubscription): int;

    /**
     * Gets a link to the first updated subrecord of the subject.
     */
    abstract public function getLink(int $subjectId, int $firstUpdateId): string;
}

class ForumTopicDelayedSubscriptionHandler extends BaseDelayedSubscriptionHandler
{
    public function getUpdatesSince(UserDelayedSubscription $delayedSubscription): int
    {
        return ForumTopicComment::query()
            ->where('forum_topic_id', $delayedSubscription->subject_id)
            ->where('id', '>=', $delayedSubscription->first_update_id)
            ->where('author_id', '!=', $delayedSubscription->user_id)
            ->where('is_authorized', true)
            ->count();
    }

    public function getLink(int $subjectId, int $firstUpdateId): string
    {
        return route('forum-topic.show', ['topic' => $subjectId, 'comment' => $firstUpdateId]) . '#' . $firstUpdateId;
    }
}

class CommentDelayedSubscriptionHandler extends BaseDelayedSubscriptionHandler
{
    protected CommentableType $commentableType;

    public function __construct(CommentableType $commentableType)
    {
        $this->commentableType = $commentableType;
    }

    public function getUpdatesSince(UserDelayedSubscription $delayedSubscription): int
    {
        return Comment::query()
            ->where('commentable_type', $this->commentableType)
            ->where('commentable_id', $delayedSubscription->subject_id)
            ->where('id', '>=', $delayedSubscription->first_update_id)
            ->where('user_id', '!=', $delayedSubscription->user_id)
            ->count();
    }

    public function getLink(int $subjectId, int $firstUpdateId): string
    {
        if ($this->commentableType->supportsCommentRedirect()) {
            return route('comment.show', ['comment' => $firstUpdateId]);
        }

        if ($this->commentableType === CommentableType::AchievementTicket) {
            return route('ticket.show', ['ticket' => $subjectId]) . "#comment_{$firstUpdateId}";
        }

        return '';
    }
}
