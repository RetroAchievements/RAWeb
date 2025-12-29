<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\ModerationReportableType;
use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\User;
use Illuminate\Support\Carbon;

class CreateMessageThreadAction
{
    public function execute(
        User $userFrom,
        User $userTo,
        User $trueSenderUser,
        string $title,
        string $body,
        bool $isProxied = false,
        ?ModerationReportableType $reportableType = null,
        ?int $reportableId = null,
    ): MessageThread {
        $thread = new MessageThread([
            'title' => $title,
        ]);
        $thread->save();

        $participantFrom = new MessageThreadParticipant([
            'user_id' => $userFrom->id,
            'thread_id' => $thread->id,
        ]);

        if ($isProxied) {
            $participantFrom->deleted_at = Carbon::now();
        }

        $participantFrom->save();

        if ($userTo->id != $userFrom->id) {
            $participantTo = new MessageThreadParticipant([
                'user_id' => $userTo->id,
                'thread_id' => $thread->id,
            ]);

            // if the recipient has blocked the sender, immediately mark the thread as deleted for the recipient
            if ($userTo->isBlocking($userFrom)) {
                $participantTo->deleted_at = Carbon::now();
            }

            $participantTo->save();
        }

        (new AddToMessageThreadAction())->execute(
            $thread,
            $userFrom,
            $trueSenderUser,
            $body,
            $reportableType,
            $reportableId,
        );

        return $thread;
    }
}
