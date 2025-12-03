<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\SubscriptionSubjectType;
use App\Mail\DailyDigestMail;
use App\Models\ForumTopic;
use App\Models\ForumTopicComment;
use App\Models\User;
use App\Models\UserDelayedSubscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendDailyDigestAction
{
    public function execute(User $user): void
    {
        if (!$user->EmailAddress) {
            return;
        }

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

        $notificationItems = [];
        foreach ($delayedSubscriptions as $delayedSubscription) {
            $handler = $this->getHandler($delayedSubscription->subject_type);
            if ($handler) {
                $count = $handler->getUpdatesSince($delayedSubscription);
                if ($count > 0) {
                    $notificationItems[] = [
                        'type' => $delayedSubscription->subject_type->value,
                        'title' => $handler->getTitle($delayedSubscription->subject_id) ?? '(untitled)',
                        'link' => $handler->getLink($delayedSubscription->subject_id, $delayedSubscription->first_update_id),
                        'count' => $count,
                    ];
                }
            }
        }

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
     * Gets the title of the subject.
     */
    abstract public function getTitle(int $subjectId): ?string;

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

    public function getTitle(int $subjectId): ?string
    {
        return ForumTopic::find($subjectId)?->title;
    }

    public function getLink(int $subjectId, int $firstUpdateId): string
    {
        return route('forum-topic.show', ['topic' => $subjectId, 'comment' => $firstUpdateId]) . '#' . $firstUpdateId;
    }
}
