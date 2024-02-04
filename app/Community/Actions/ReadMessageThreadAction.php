<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Models\MessageThread;
use App\Community\Models\MessageThreadParticipant;
use App\Models\User;

class ReadMessageThreadAction
{
    public function execute(MessageThread $thread, User $user): void
    {
        $participant = MessageThreadParticipant::where('user_id', $user->id)
            ->where('thread_id', $thread->id)
            ->whereNull('deleted_at')
            ->first();

        if ($participant) {
            ReadMessageThreadAction::markParticipantRead($participant, $user);
        }
    }

    public static function markParticipantRead(MessageThreadParticipant $participant, User $user): void
    {
        if ($participant->num_unread) {
            $participant->num_unread = 0;
            $participant->save();

            (new UpdateUnreadMessageCountAction())->execute($user);
        }
    }
}
