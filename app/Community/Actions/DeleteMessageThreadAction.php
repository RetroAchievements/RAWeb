<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\User;

class DeleteMessageThreadAction
{
    public function execute(MessageThread $thread, User $user): void
    {
        $participant = MessageThreadParticipant::where('thread_id', $thread->id)
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            // make sure num_unread is 0 before we soft-delete the record.
            if ($participant->num_unread) {
                $participant->num_unread = 0;
                $participant->save();
            }

            $participant->delete();

            (new UpdateUnreadMessageCountAction())->execute($user);

            $hasOtherActiveParticipants = MessageThreadParticipant::where('thread_id', $thread->id)
                ->where('user_id', '!=', $user->id)
                ->whereNull('deleted_at')
                ->exists();
            if (!$hasOtherActiveParticipants) {
                // this will also cascade delete the message_participants and messages
                $thread->delete();
            }
        }
    }
}
