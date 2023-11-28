<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Actions\UpdateUnreadMessageCountAction;
use App\Community\Models\MessageThread;
use App\Community\Models\MessageThreadParticipant;
use App\Site\Models\User;
use Illuminate\Support\Carbon;

class DeleteMessageThreadAction
{
    public function execute(MessageThread $thread, User $user): void
    {
        $participant = MessageThreadParticipant::where('thread_id', $thread->id)
            ->where('user_id', $user->id)
            ->first();

        if ($participant) {
            $participant->num_unread = 0;
            $participant->deleted_at = Carbon::now();
            $participant->save();

            (new UpdateUnreadMessageCountAction)->execute($user);

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