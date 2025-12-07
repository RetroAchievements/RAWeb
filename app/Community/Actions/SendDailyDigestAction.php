<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\SubscriptionSubjectType;
use App\Mail\DailyDigestMail;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\User;
use App\Models\UserDelayedSubscription;
use Illuminate\Support\Facades\Mail;

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

        // if the user doesn't have an email address, bail.
        // do this after deleting the pending subscriptions.
        if (!$user->EmailAddress) {
            return;
        }

        // build a map of ids for each type
        $ids = [];
        foreach ($delayedSubscriptions as $delayedSubscription) {
            if (!isset($ids[$delayedSubscription->subject_type->value])) {
                $ids[$delayedSubscription->subject_type->value] = [];
            }
            $ids[$delayedSubscription->subject_type->value][] = $delayedSubscription->subject_id;
        }
        // and preload the titles for the associated records
        $titles = [];
        foreach ($ids as $type => $typeIds) {
            $titles[$type] = match ($type) {
                SubscriptionSubjectType::ForumTopic->value => ForumTopic::whereIn('id', $typeIds)->pluck('title', 'id'),
                default => [],
            };
        }

        // build the data to pass to the mail script
        $notificationItems = [];
        foreach ($delayedSubscriptions as $delayedSubscription) {
            $handler = $this->getHandler($delayedSubscription->subject_type);
            if ($handler) {
                // if all the new posts have been deleted or aren't visible, ignore it
                $count = $handler->getUpdatesSince($delayedSubscription);
                if ($count > 0) {
                    $notificationItems[] = [
                        'type' => $delayedSubscription->subject_type->value,
                        'title' => $titles[$delayedSubscription->subject_type->value][$delayedSubscription->subject_id] ?? '(untitled)',
                        'link' => $handler->getLink($delayedSubscription->subject_id, $delayedSubscription->first_update_id),
                        'count' => $count,
                    ];
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
        };
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
