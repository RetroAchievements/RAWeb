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
use App\Models\GameScreenshot;
use App\Models\Leaderboard;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserDelayedSubscription;
use App\Platform\Enums\GameScreenshotRejectionReason;
use App\Platform\Enums\GameScreenshotStatus;
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

        // if the user doesn't have an email address, is opted out, or is inactive, bail.
        // do this after deleting the pending subscriptions.
        if (
            !$user->email
            || BitSet($user->preferences_bitfield, UserPreference::EmailOff_DailyDigest)
            || $user->isInactive()
        ) {
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
        // and preload the titles/details for the associated records
        $titles = [];

        $screenshotDecisionData = [];
        $screenshotGamesByScreenshotId = [];
        foreach ($ids as $type => $typeIds) {
            if ($type === SubscriptionSubjectType::GameScreenshotDecision->value) {
                $screenshots = GameScreenshot::whereIn('id', $typeIds)->with('game.system')->get();

                foreach ($screenshots as $screenshot) {
                    $titles[$type][$screenshot->id] = "{$screenshot->game->title} ({$screenshot->game->system->name})";
                    $screenshotGamesByScreenshotId[$screenshot->id] = $screenshot->game;

                    $data = [];
                    if ($screenshot->status instanceof GameScreenshotStatus) {
                        $data['status'] = $screenshot->status->value;
                    }
                    if ($screenshot->rejection_reason instanceof GameScreenshotRejectionReason) {
                        $data['rejectionReason'] = $screenshot->rejection_reason->label();
                    }
                    if ($screenshot->rejection_notes !== null && $screenshot->rejection_notes !== '') {
                        $data['rejectionNotes'] = $screenshot->rejection_notes;
                    }

                    $screenshotDecisionData[$screenshot->id] = $data;
                }
            } else {
                $titles[$type] = match ($type) {
                    SubscriptionSubjectType::ForumTopic->value => ForumTopic::whereIn('id', $typeIds)->pluck('title', 'id'),
                    SubscriptionSubjectType::GameWall->value => $this->buildGameWallTitles($typeIds),
                    SubscriptionSubjectType::Achievement->value => $this->buildAchievementWallTitles($typeIds),
                    SubscriptionSubjectType::UserWall->value => User::whereIn('id', $typeIds)->pluck('display_name', 'id'),
                    SubscriptionSubjectType::Leaderboard->value => $this->buildLeaderboardTitles($typeIds),
                    SubscriptionSubjectType::AchievementTicket->value => $this->buildTicketTitles($typeIds),
                    default => [],
                };
            }
        }

        // build the data to pass to the mail script
        $singleItems = [];
        $notificationItems = [];
        foreach ($delayedSubscriptions as $delayedSubscription) {
            // if all the new posts have been deleted or aren't visible, ignore it
            $handler = $this->getHandler($delayedSubscription->subject_type, $screenshotGamesByScreenshotId);
            $count = $handler->getUpdatesSince($delayedSubscription);
            if ($count > 0) {
                $notificationItem = [
                    'type' => $delayedSubscription->subject_type->value,
                    'title' => $titles[$delayedSubscription->subject_type->value][$delayedSubscription->subject_id] ?? '(untitled)',
                    'link' => $handler->getLink($delayedSubscription->subject_id, $delayedSubscription->first_update_id),
                    'count' => $count,
                ];

                if ($delayedSubscription->subject_type === SubscriptionSubjectType::GameScreenshotDecision) {
                    $notificationItem = [
                        ...$notificationItem,
                        ...($screenshotDecisionData[$delayedSubscription->subject_id] ?? []),
                    ];
                }

                $notificationItems[] = $notificationItem;

                if ($count === 1 && $delayedSubscription->subject_type !== SubscriptionSubjectType::GameScreenshotDecision) {
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

        $notificationItems = $this->aggregateScreenshotDecisionItems($notificationItems);

        // send the mail
        Mail::to($user->email)->queue(
            new DailyDigestMail($user, $notificationItems)
        );
    }

    /**
     * @param array<int, Game> $screenshotGamesByScreenshotId
     */
    private function getHandler(SubscriptionSubjectType $subjectType, array $screenshotGamesByScreenshotId = []): BaseDelayedSubscriptionHandler
    {
        if ($subjectType === SubscriptionSubjectType::GameScreenshotDecision) {
            $handler = new GameScreenshotDecisionDelayedSubscriptionHandler();
            $handler->setPreloadedGames($screenshotGamesByScreenshotId);

            return $handler;
        }

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

        $games = Game::whereIn('id', $ids)->with('system')->get();
        foreach ($games as $game) {
            $result[$game->id] = "{$game->title} ({$game->system->name})";
        }

        return $result;
    }

    private function buildAchievementWallTitles(array $ids): array
    {
        $result = [];

        $achievements = Achievement::whereIn('id', $ids)->with('game')->get();
        foreach ($achievements as $achievement) {
            $result[$achievement->id] = "{$achievement->title} ({$achievement->game->title})";
        }

        return $result;
    }

    private function buildLeaderboardTitles(array $ids): array
    {
        $result = [];

        $leaderboards = Leaderboard::whereIn('id', $ids)->with('game')->get();
        foreach ($leaderboards as $leaderboard) {
            $result[$leaderboard->id] = "{$leaderboard->title} ({$leaderboard->game->title})";
        }

        return $result;
    }

    private function buildTicketTitles(array $ids): array
    {
        $result = [];

        $tickets = Ticket::whereIn('id', $ids)->with('ticketable')->get();
        foreach ($tickets as $ticket) {
            if (!$ticket->ticketable) {
                continue;
            }

            $result[$ticket->id] = $ticket->getTicketableModel()->getTicketableTitle();
        }

        return $result;
    }

    /**
     * @param array<int, array<string, mixed>> $notificationItems
     * @return array<int, array<string, mixed>>
     */
    private function aggregateScreenshotDecisionItems(array $notificationItems): array
    {
        $groupedScreenshotItems = [];
        foreach ($notificationItems as $item) {
            if (($item['type'] ?? null) !== SubscriptionSubjectType::GameScreenshotDecision->value) {
                continue;
            }

            $gameKey = ($item['link'] ?? '') . '|' . ($item['title'] ?? '');
            $groupedScreenshotItems[$gameKey][] = $item;
        }

        $needsAggregation = collect($groupedScreenshotItems)->contains(fn (array $items) => count($items) > 1);
        if (!$needsAggregation) {
            return $notificationItems;
        }

        $aggregatedItems = [];
        $processedGameKeys = [];

        foreach ($notificationItems as $item) {
            if (($item['type'] ?? null) === SubscriptionSubjectType::GameScreenshotDecision->value) {
                $gameKey = ($item['link'] ?? '') . '|' . ($item['title'] ?? '');
                if (in_array($gameKey, $processedGameKeys, true)) {
                    continue;
                }

                $gameItems = $groupedScreenshotItems[$gameKey] ?? [$item];
                $aggregatedItems[] = count($gameItems) === 1
                    ? $gameItems[0]
                    : $this->summarizeScreenshotDecisionGameItems($gameItems);
                $processedGameKeys[] = $gameKey;

                continue;
            }

            $aggregatedItems[] = $item;
        }

        return $aggregatedItems;
    }

    /**
     * @param array<string, int> $rejectionReasons
     */
    private function summarizeScreenshotRejectionReasons(array $rejectionReasons): ?string
    {
        if (empty($rejectionReasons)) {
            return null;
        }

        $parts = [];
        foreach ($rejectionReasons as $reason => $count) {
            $parts[] = $count === 1 ? $reason : "{$reason} x{$count}";
        }

        return implode(', ', $parts);
    }

    /**
     * @param array<int, array<string, mixed>> $gameItems
     * @return array<string, mixed>
     */
    private function summarizeScreenshotDecisionGameItems(array $gameItems): array
    {
        $approvedCount = 0;
        $rejectedCount = 0;
        $reviewedCount = 0;
        $rejectionReasons = [];
        $rejectedItems = [];

        foreach ($gameItems as $item) {
            match ($item['status'] ?? null) {
                GameScreenshotStatus::Approved->value => $approvedCount++,
                GameScreenshotStatus::Rejected->value => $rejectedCount++,
                default => $reviewedCount++,
            };

            if (($item['status'] ?? null) === GameScreenshotStatus::Rejected->value && ($item['rejectionReason'] ?? null)) {
                $reason = $item['rejectionReason'];
                $rejectionReasons[$reason] = ($rejectionReasons[$reason] ?? 0) + 1;

                $rejectedItems[] = [
                    'reason' => $reason,
                    'notes' => $item['rejectionNotes'] ?? null,
                ];
            }
        }

        return [
            'type' => SubscriptionSubjectType::GameScreenshotDecision->value,
            'title' => $gameItems[0]['title'] ?? null,
            'link' => $gameItems[0]['link'] ?? null,
            'count' => count($gameItems),
            'gameCount' => 1,
            'approvedCount' => $approvedCount,
            'rejectedCount' => $rejectedCount,
            'reviewedCount' => $reviewedCount,
            'rejectionReasonSummary' => $this->summarizeScreenshotRejectionReasons($rejectionReasons),
            'rejectedItems' => $rejectedItems,
        ];
    }
}

abstract class BaseDelayedSubscriptionHandler
{
    /**
     * Gets the number of updates that have been made by users other than the subscriber since the notification was delayed.
     */
    abstract public function getUpdatesSince(UserDelayedSubscription &$delayedSubscription): int;

    /**
     * Gets a link to the first updated subrecord of the subject.
     */
    abstract public function getLink(int $subjectId, int $firstUpdateId): string;
}

class ForumTopicDelayedSubscriptionHandler extends BaseDelayedSubscriptionHandler
{
    public function getUpdatesSince(UserDelayedSubscription &$delayedSubscription): int
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

    public function getUpdatesSince(UserDelayedSubscription &$delayedSubscription): int
    {
        // If the first comment was deleted, find the next valid one.
        if (!Comment::where('id', $delayedSubscription->first_update_id)->exists()) {
            $nextComment = Comment::where('commentable_type', $this->commentableType)
                ->where('commentable_id', $delayedSubscription->subject_id)
                ->where('id', '>', $delayedSubscription->first_update_id)
                ->where('user_id', '!=', $delayedSubscription->user_id)
                ->where('user_id', '!=', Comment::SYSTEM_USER_ID)
                ->orderBy('id')
                ->first();

            // If there isn't a valid one, don't include the notification.
            if (!$nextComment) {
                return 0;
            }

            $delayedSubscription->first_update_id = $nextComment->id;
        }

        return Comment::query()
            ->where('commentable_type', $this->commentableType)
            ->where('commentable_id', $delayedSubscription->subject_id)
            ->where('id', '>=', $delayedSubscription->first_update_id)
            ->where('user_id', '!=', $delayedSubscription->user_id)
            ->where('user_id', '!=', Comment::SYSTEM_USER_ID)
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

class GameScreenshotDecisionDelayedSubscriptionHandler extends BaseDelayedSubscriptionHandler
{
    /** @var array<int, Game> */
    private array $gamesByScreenshotId = [];

    /**
     * @param array<int, Game> $gamesByScreenshotId
     */
    public function setPreloadedGames(array $gamesByScreenshotId): void
    {
        $this->gamesByScreenshotId = $gamesByScreenshotId;
    }

    public function getUpdatesSince(UserDelayedSubscription &$delayedSubscription): int
    {
        return 1; // each subscription represents exactly one decision
    }

    public function getLink(int $subjectId, int $firstUpdateId): string
    {
        $game = $this->gamesByScreenshotId[$subjectId] ?? null;
        if (!$game) {
            return '';
        }

        return route('game.show', ['game' => $game]);
    }
}
