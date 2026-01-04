<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\ModerationReportableType;
use App\Community\Events\MessageCreated;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use App\Models\UserModerationReport;
use Illuminate\Support\Carbon;

class AddToMessageThreadAction
{
    public function execute(
        MessageThread $thread,
        User $userFrom,
        User $trueSenderUser,
        string $body,
        ?ModerationReportableType $reportableType = null,
        ?int $reportableId = null,
    ): void {
        // Prepend report context to the message body before saving to the database.
        if ($reportableType && $reportableId) {
            $body = (new BuildReportContextAction())->execute(
                $body,
                $reportableType,
                $reportableId,
                forDiscord: false
            );
        }

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

        // Create a moderation report record if this is a report.
        $moderationReportId = null;
        if ($reportableType && $reportableId) {
            $reportedItem = $reportableType->getReportedItem($reportableId);
            $reportedUserId = $reportedItem?->user_id ?? $reportedItem?->author_id ?? $reportedItem?->id ?? null;

            $moderationReport = UserModerationReport::create([
                'reporter_user_id' => $trueSenderUser->id,
                'reported_user_id' => $reportedUserId,
                'reportable_type' => $reportableType->value,
                'reportable_id' => $reportableId,
                'message_thread_id' => $thread->id,
            ]);

            $moderationReportId = $moderationReport->id;
        }

        MessageCreated::dispatch($message, $moderationReportId);
    }
}
