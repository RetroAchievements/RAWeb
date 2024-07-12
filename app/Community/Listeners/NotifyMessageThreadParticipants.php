<?php

declare(strict_types=1);

namespace App\Community\Listeners;

use App\Community\Actions\UpdateUnreadMessageCountAction;
use App\Community\Events\MessageCreated;
use App\Enums\UserPreference;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\MessageThreadParticipant;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class NotifyMessageThreadParticipants
{
    public function handle(MessageCreated $event): void
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

            if ($userTo->isBlocking($userFrom)) {
                // ignore users who have blocked the sender
                continue;
            }

            // use direct update to avoid race condition
            DB::statement("UPDATE message_thread_participants
                           SET num_unread = num_unread + 1, deleted_at = NULL
                           WHERE id = {$participant->id}");

            $updateUnreadMessageCountAction->execute($userTo);

            // send email?
            if (BitSet($userTo->websitePrefs, UserPreference::EmailOn_PrivateMessage)) {
                sendPrivateMessageEmail($userTo->User, $userTo->EmailAddress, $thread->title, $message->body, $userFrom->User);
            }

            $this->forwardToDiscord($userFrom, $userTo, $thread, $message);
        }
    }

    private function forwardToDiscord(
        User $userFrom,
        User $userTo,
        MessageThread $messageThread,
        Message $message
    ): void {
        $inboxConfig = config('services.discord.inbox_webhook.' . $userTo->username);
        $webhookUrl = $inboxConfig['url'] ?? null;

        if ($inboxConfig === null || $webhookUrl === null) {
            return;
        }

        if (empty($messageThread->title) || empty($message->body)) {
            return;
        }

        // Thread names cannot be over 100 characters long, otherwise the webhook POST will fail.
        $truncatedTitle = mb_strimwidth($messageThread->title, 0, 95, '...');

        $color = hexdec('0x0066CC');
        $mentionRoles = collect(Arr::wrap($inboxConfig['mention_role'] ?? []))
            ->map(fn ($role) => '<@&' . $role . '>');
        $isForum = $inboxConfig['is_forum'] ?? false;

        if (mb_strpos(mb_strtolower($messageThread->title), 'verify') !== false
            || mb_strpos(mb_strtolower($messageThread->title), 'verified') !== false
            || mb_strpos(mb_strtolower($messageThread->title), 'verifying') !== false
            || mb_strpos(mb_strtolower($messageThread->title), 'verification') !== false
            || mb_strpos(mb_strtolower($messageThread->title), 'discord') !== false
        ) {
            $webhookUrl = $inboxConfig['verify_url'];
            $color = hexdec('0x00CC66');
            $mentionRoles = collect();
            $isForum = false;
        }

        if (mb_strpos(mb_strtolower($messageThread->title), 'delete') !== false
            || mb_strpos(mb_strtolower($messageThread->title), 'deleting') !== false
            || mb_strpos(mb_strtolower($messageThread->title), 'deletion') !== false) {
            $color = hexdec('0xCC6600');
        }

        if (mb_strpos(mb_strtolower($messageThread->title), 'manual') !== false) {
            $webhookUrl = $inboxConfig['manual_unlock_url'];
            $color = hexdec('0xCC0066');
            $mentionRoles = collect();
            $isForum = false;
        }

        if (mb_strpos(mb_strtolower($messageThread->title), 'unwelcome concept') !== false) {
            $webhookUrl = $inboxConfig['unwelcome_concept_url'];
            $mentionRoles = collect();
            $isForum = true;
        }

        $payload = [
            'username' => $userTo->username . ' Inbox',
            'avatar_url' => $userTo->avatar_url,
            'embeds' => [
                [
                    'author' => [
                        'name' => $userFrom->username,
                        // TODO 'url' => route('user.show', $userFrom),
                        'url' => url('user/' . $userFrom->username),
                        'icon_url' => $userFrom->avatar_url,
                    ],
                    'title' => $truncatedTitle,
                    'url' => route('message-thread.show', ['messageThread' => $messageThread->id]),
                    'description' => mb_substr($message->body, 0, 2000),
                    'color' => $color,
                ],
            ],
        ];

        if ($mentionRoles->isNotEmpty()) {
            $payload['content'] = $mentionRoles->implode(' ');
        }

        if ($isForum) {
            // Forum channels require an additional 'thread_name' JSON parameter to be successfully posted.
            $payload['thread_name'] = $truncatedTitle;
        }

        (new Client())->post($webhookUrl, ['json' => $payload]);
    }
}
