<?php

declare(strict_types=1);

namespace App\Community\Listeners;

use App\Community\Actions\ForwardMessageToDiscordAction;
use App\Community\Actions\UpdateUnreadMessageCountAction;
use App\Community\Events\MessageCreated;
use App\Enums\UserPreference;
use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\User;
use App\Notifications\Message\PrivateMessageReceivedNotification;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotifyMessageThreadParticipants
{
    public function handle(MessageCreated $event): void
    {
        $message = $event->message;

        $userFrom = User::firstWhere('id', $message->author_id);
        if (!$userFrom) {
            return;
        }

        $thread = MessageThread::firstWhere('id', $message->thread_id);
        if (!$thread) {
            return;
        }

        $updateUnreadMessageCountAction = new UpdateUnreadMessageCountAction();

        $participants = MessageThreadParticipant::withTrashed()->where('thread_id', $message->thread_id)->get();
        foreach ($participants as $participant) {
            if ($participant->user_id == $message->author_id) {
                // don't notify the sender
                continue;
            }

            $userTo = User::firstWhere('id', $participant->user_id);
            if (!$userTo) {
                // ignore deleted users
                continue;
            }

            if ($userTo->isBlocking($userFrom)) {
                // ignore users who have blocked the sender
                continue;
            }

            // use direct update to avoid race condition
            DB::statement("UPDATE message_thread_participants
                           SET num_unread = num_unread + 1, deleted_at = NULL
                           WHERE id = {$participant->id}");

            $updateUnreadMessageCountAction->execute($userTo);

            // Send an email notification.
            if (BitSet($userTo->preferences_bitfield, UserPreference::EmailOn_PrivateMessage)) {
                if (!$userTo->is($userFrom)) {
                    $userTo->notify(new PrivateMessageReceivedNotification(
                        $userFrom,
                        $thread,
                        $message,
                    ));
                }
            }

            try {
                (new ForwardMessageToDiscordAction())->execute(
                    $userFrom,
                    $userTo,
                    $thread,
                    $message,
                    $event->moderationReportId
                );
            } catch (Exception $e) {
                Log::warning('Discord notification failed', [
                    'thread_id' => $thread->id,
                    'user_from' => $userFrom->username,
                    'user_to' => $userTo->username,
                    'error_message' => $e->getMessage(),
                    'error_class' => get_class($e),
                ]);
            }
        }
    }
}
