<?php

declare(strict_types=1);

namespace App\Community\Listeners;

use App\Community\Actions\UpdateUnreadMessageCountAction;
use App\Community\Models\MessageThread;
use App\Community\Models\MessageThreadParticipant;
use App\Site\Enums\UserPreference;
use App\Site\Models\User;
use Illuminate\Support\Facades\DB;

class NotifyMessageThreadParticipants
{
    public function handle(object $event): void
    {
        $message = $event->message;

        $userFrom = User::firstWhere('ID', $message->author_id);
        if (!$userFrom) {
            return;
        }

        $thread = MessageThread::firstWhere('id', $message->thread_id);
        if (!$thread) {
            return;
        }

        $updateUnreadMessageCountAction = new UpdateUnreadMessageCountAction();

        $validParticipants = 0;
        $participants = MessageThreadParticipant::withTrashed()->where('thread_id', $message->thread_id)->get();
        foreach ($participants as $participant) {
            if ($participant->user_id == $message->author_id) {
                // don't notify the sender
                continue;
            }

            $userTo = User::firstWhere('ID', $participant->user_id);
            if (!$userTo) {
                // ignore deleted users
                continue;
            }

            if ($userTo->isBlocking($userFrom->User)) {
                // ignore users who have blocked the sender
                continue;
            }

            $validParticipants++;

            // use direct update to avoid race condition
            DB::statement("UPDATE message_thread_participants
                           SET num_unread = num_unread + 1, deleted_at = NULL
                           WHERE id = {$participant->id}");

            $updateUnreadMessageCountAction->execute($userTo);

            // send email?
            if (BitSet($userTo->websitePrefs, UserPreference::EmailOn_PrivateMessage)) {
                sendPrivateMessageEmail($userTo->User, $userTo->EmailAddress, $thread->title, $message->body, $userFrom->User);
            }
        }
    }
}
