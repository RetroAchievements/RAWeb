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
use App\Support\Shortcode\Shortcode;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        Log::info("[{$messageThread->title}] Discord forward: Starting message forward", [
            'message_id' => $message->id,
            'thread_id' => $messageThread->id,
            'from_user' => $userFrom->username,
            'to_user' => $userTo->username,
            'original_title' => $messageThread->title,
            'message_length' => mb_strlen($message->body),
        ]);

        $message->body = Shortcode::stripAndClamp($message->body, 1850, preserveWhitespace: true);
        Log::info("[{$messageThread->title}] Discord forward: Message body processed", [
            'message_id' => $message->id,
            'thread_id' => $messageThread->id,
            'from_user' => $userFrom->username,
            'to_user' => $userTo->username,
            'original_title' => $messageThread->title,
            'processed_length' => mb_strlen($message->body),
            'truncated' => mb_strlen($message->body) === 1850,
            'body' => $message->body,
        ]);

        $inboxConfig = config('services.discord.inbox_webhook.' . $userTo->username);
        $webhookUrl = $inboxConfig['url'] ?? null;

        if ($inboxConfig === null || empty($webhookUrl)) {
            return;
        }

        if (empty($messageThread->title) || empty($message->body)) {
            return;
        }

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
            Log::info("[{$messageThread->title}] Discord forward: Verification message detected", [
                'message_id' => $message->id,
                'thread_id' => $messageThread->id,
            ]);
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

        // If true, has title like "Kind: Achievement Name [Achievement ID] (Game Name)"
        $structuredTitlePrefixes = [
            'Incorrect type:' => 'incorrect_type_url',
            'Issue:' => 'achievement_issues_url',
            'Unwelcome Concept:' => 'unwelcome_concept_url',
            'Writing:' => 'url',
        ];
        foreach ($structuredTitlePrefixes as $prefix => $configKey) {
            if (mb_strpos($messageThread->title, $prefix) !== false) {
                Log::info("[{$messageThread->title}] Discord forward: Structured title message detected", [
                    'message_id' => $message->id,
                    'thread_id' => $messageThread->id,
                    'from_user' => $userFrom->username,
                    'to_user' => $userTo->username,
                    'original_title' => $messageThread->title,
                    'processed_length' => mb_strlen($message->body),
                    'truncated' => mb_strlen($message->body) === 1850,
                    'prefix' => $prefix,
                    'body' => $message->body,
                ]);
                $webhookUrl = $inboxConfig[$configKey];
                $mentionRoles = collect();
                $isForum = true;

                // Extract the achievement ID from the message thread title.
                // We'll auto-insert a link to the achievement at the top of the message.
                if (preg_match('/\[([0-9]+)\]/', $messageThread->title, $matches)) {
                    $achievementId = $matches[1];
                    $achievementUrl = route('achievement.show', $achievementId);
                    $message->body = $achievementUrl . "\n\n" . $message->body;

                    Log::info("[{$messageThread->title}] Discord forward: Added achievement URL to message", [
                        'message_id' => $message->id,
                        'thread_id' => $messageThread->id,
                        'from_user' => $userFrom->username,
                        'to_user' => $userTo->username,
                        'original_title' => $messageThread->title,
                        'processed_length' => mb_strlen($message->body),
                        'truncated' => mb_strlen($message->body) === 1850,
                        'prefix' => $prefix,
                        'body' => $message->body,
                    ]);

                    // We want to reformat the incoming structured title before it lands in the team forum.
                    //  - Original:  "Unwelcome Concept: Lots of Rings [12345] (Sonic the Hedgehog)"
                    //  - Formatted: "12345: Lots of Rings (Sonic the Hedgehog)"
                    if (preg_match('/^(Incorrect type:|Issue:|Unwelcome Concept:|Writing:)\s*(.*)\s*\[([0-9]+)\]\s*(\(.*\))$/', $messageThread->title, $titleMatches)) {
                        $originalTitle = $messageThread->title;
                        $newTitle = $achievementId . ': ' . $titleMatches[2] . ' ' . $titleMatches[4];
                        $messageThread->title = $newTitle;

                        Log::info("[{$messageThread->title}] Discord forward: Reformatted thread title", [
                            'message_id' => $message->id,
                            'thread_id' => $messageThread->id,
                            'from_user' => $userFrom->username,
                            'to_user' => $userTo->username,
                            'original_title' => $originalTitle,
                            'new_title' => $newTitle,
                            'processed_length' => mb_strlen($message->body),
                            'truncated' => mb_strlen($message->body) === 1850,
                            'prefix' => $prefix,
                            'body' => $message->body,
                        ]);
                    }
                }

                break;
            }
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
                    'title' => mb_substr($messageThread->title, 0, 100),
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
            $payload['thread_name'] = mb_substr($messageThread->title, 0, 100);
            Log::info("[{$messageThread->title}] Discord forward: Preparing forum thread", [
                'message_id' => $message->id,
                'thread_id' => $messageThread->id,
                'from_user' => $userFrom->username,
                'to_user' => $userTo->username,
                'thread_name' => $payload['thread_name'],
                'has_content' => isset($payload['content']),
                'body_length' => mb_strlen($message->body),
                'is_truncated_title' => mb_strlen($messageThread->title) > 100,
                'is_truncated_body' => mb_strlen($message->body) > 2000,
                'body' => $message->body,
                'payload' => $payload,
            ]);
        }

        try {
            $client = new Client();
            $response = $client->post($webhookUrl, ['json' => $payload]);

            if ($isForum) {
                Log::info("[{$messageThread->title}] Discord forward: Request successful", [
                    'message_id' => $message->id,
                    'thread_id' => $messageThread->id,
                    'status_code' => $response->getStatusCode(),
                    'response_body' => (string) $response->getBody(),
                    'response_headers' => $response->getHeaders(),
                ]);
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
            $context = [
                'message_id' => $message->id,
                'thread_id' => $messageThread->id,
                'is_forum' => $isForum,
                'error' => $e->getMessage(),
            ];

            if ($response) {
                $context['status_code'] = $response->getStatusCode();
                $context['response_body'] = (string) $response->getBody();
                $context['response_headers'] = $response->getHeaders();
            }

            Log::error("[{$messageThread->title}] Discord forward: Request failed", $context);

            throw $e;
        } catch (Exception $e) {
            Log::error("[{$messageThread->title}] Discord forward: Unexpected error", [
                'message_id' => $message->id,
                'thread_id' => $messageThread->id,
                'is_forum' => $isForum,
                'error_class' => get_class($e),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
