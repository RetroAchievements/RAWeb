<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\User;
use Illuminate\Support\Carbon;

class CreateMessageThreadAction
{
    public function execute(User $userFrom, User $userTo, string $title, string $body, bool $isProxied = false): MessageThread
    {
        $thread = new MessageThread([
            'title' => $title,
        ]);
        $thread->save();

        $participantFrom = new MessageThreadParticipant([
            'user_id' => $userFrom->ID,
            'thread_id' => $thread->id,
        ]);

        if ($isProxied) {
            $participantFrom->deleted_at = Carbon::now();
        }

        $participantFrom->save();

        if ($userTo->ID != $userFrom->ID) {
            $participantTo = new MessageThreadParticipant([
                'user_id' => $userTo->ID,
                'thread_id' => $thread->id,
            ]);

            // if the recipient has blocked the sender, immediately mark the thread as deleted for the recipient
            if ($userTo->isBlocking($userFrom->User)) {
                $participantTo->deleted_at = Carbon::now();
            }

            $participantTo->save();
        }

        (new AddToMessageThreadAction())->execute($thread, $userFrom, $body);

        return $thread;
    }
}
