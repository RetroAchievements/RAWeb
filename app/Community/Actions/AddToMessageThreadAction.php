<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Events\MessageCreated;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Support\Carbon;

class AddToMessageThreadAction
{
    public function execute(
        MessageThread $thread,
        User $userFrom,
        User $trueSenderUser,
        string $body
    ): void {
        $message = new Message([
            'thread_id' => $thread->id,
            'author_id' => $userFrom->id,
            'sent_by_id' => !$userFrom->is($trueSenderUser) ? $trueSenderUser->id : null,
            'body' => $body,
            'created_at' => Carbon::now(),
        ]);
        $message->save();

        $thread->num_messages++;
        $thread->last_message_id = $message->id;
        $thread->save();

        MessageCreated::dispatch($message);
    }
}
