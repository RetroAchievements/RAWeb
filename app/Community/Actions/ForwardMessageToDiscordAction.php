<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Data\ProcessedDiscordMessageData;
use App\Community\Enums\ModerationReportableType;
use App\Models\DiscordMessageThreadMapping;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use App\Models\UserModerationReport;
use App\Support\Shortcode\Shortcode;
use GuzzleHttp\Client;

class ForwardMessageToDiscordAction
{
    /** Discord API limits */
    private const DISCORD_EMBED_DESCRIPTION_LIMIT = 2000;
    private const DISCORD_THREAD_NAME_LIMIT = 100;

    /** Message processing limits */
    private const MESSAGE_BODY_MAX_LENGTH = 10_000;

    /** Timing constants */
    private const CHUNK_SEND_DELAY_MICROSECONDS = 100_000;

    /** Embed colors */
    private const COLOR_DEFAULT = 0x0066CC;
    private const COLOR_VERIFICATION = 0x00CC66;
    private const COLOR_MANUAL_UNLOCK = 0xCC0066;

    /** Discord forum tag IDs */
    private const DISCORD_TAG_MOD_REPORTS_OPEN = '1442949578629578882';

    private Client $client;

    public function __construct(?Client $client = null)
    {
        // Optionally inject a mock client in tests to avoid real Discord API calls.
        $this->client = $client ?? new Client();
    }

    /**
     * Forward a message to Discord via webhook.
     */
    public function execute(
        User $userFrom,
        User $userTo,
        MessageThread $messageThread,
        Message $message,
        ?int $moderationReportId = null,
    ): void {
        // Load the moderation report if this is a report message.
        $moderationReport = null;
        $reportableType = null;
        $reportableId = null;

        if ($moderationReportId) {
            $moderationReport = UserModerationReport::find($moderationReportId);
            if ($moderationReport) {
                $reportableType = ModerationReportableType::from($moderationReport->reportable_type);
                $reportableId = $moderationReport->reportable_id;
            }
        }

        $inboxConfig = config('services.discord.inbox_webhook.' . $userTo->username);

        // Check if this is a reply from a team account to an existing Discord thread.
        // If it is, we'll also put the reply in the Discord thread just for the sake
        // of continuity.
        $hasAnyWebhookUrl = (
            !empty($inboxConfig['url'] ?? null)
            || !empty($inboxConfig['reports_url'] ?? null)
            || !empty($inboxConfig['verify_url'] ?? null)
            || !empty($inboxConfig['manual_unlock_url'] ?? null)
        );

        if ($inboxConfig === null || !$hasAnyWebhookUrl) {
            $existingMapping = DiscordMessageThreadMapping::findMapping($messageThread->id);
            if ($existingMapping) {
                // This thread is already being tracked in Discord.
                // Check if the sender (team account) has a webhook config.
                $senderInboxConfig = config('services.discord.inbox_webhook.' . $userFrom->username);

                if ($senderInboxConfig !== null) {
                    // Forward the team's reply to the existing Discord thread.
                    $this->forwardTeamReplyToDiscord(
                        $senderInboxConfig,
                        $existingMapping->discord_thread_id,
                        $userFrom,
                        $messageThread,
                        $message
                    );
                }
            }

            return;
        }

        // Set default webhook URL. This may be overridden by specialized routing logic below.
        $webhookUrl = '';
        if (!empty($inboxConfig['url'])) {
            $webhookUrl = $inboxConfig['url'];
        } elseif (!empty($inboxConfig['reports_url'])) {
            $webhookUrl = $inboxConfig['reports_url'];
        }

        // Get forum configuration early since we need it for thread lookup.
        $isForum = $inboxConfig['is_forum'] ?? false;

        // Check for an existing Discord thread.
        $existingThreadId = null;
        $isExistingReport = false;

        if ($isForum) {
            $existingThreadId = $this->getExistingDiscordThreadId($messageThread);
        }

        // For reports, also check if this specific reportable item has been reported before.
        // This helps us determine if we should prepend context (new report) or not (existing report).
        if ($reportableType && $reportableId && !$existingThreadId) {
            $reportMapping = DiscordMessageThreadMapping::findReportMapping($reportableType, $reportableId);
            $existingThreadId = $reportMapping?->discord_thread_id;
            $isExistingReport = $reportMapping !== null;
        }

        // For new reports, strip shortcode context and build fresh Discord context.
        // For replies and existing reports, convert the entire body normally.
        if ($reportableType && $reportableId && !$isExistingReport) {
            $reportDetailsMarker = "[b]Report Details:[/b]\n";
            $userDescription = $message->body;
            $markerPos = mb_strpos($message->body, $reportDetailsMarker);
            if ($markerPos !== false) {
                $userDescription = mb_substr($message->body, $markerPos + mb_strlen($reportDetailsMarker));
            }

            // Convert only the user description to markdown.
            $convertedDescription = Shortcode::convertToMarkdown($userDescription, self::MESSAGE_BODY_MAX_LENGTH, preserveWhitespace: true);

            // Build Discord context with excerpt and Discord timestamp.
            $discordContext = (new BuildReportContextAction())->execute('', $reportableType, $reportableId, forDiscord: true);

            $fullBody = $discordContext . $convertedDescription;
        } else {
            // Normal flow for non-reports, existing reports, and replies.
            $fullBody = Shortcode::convertToMarkdown($message->body, self::MESSAGE_BODY_MAX_LENGTH, preserveWhitespace: true);
        }

        if (empty($messageThread->title) || empty($fullBody)) {
            return;
        }

        $color = self::COLOR_DEFAULT;
        $isNewThread = $isForum ? !$existingThreadId : true;

        $processedData = $this->processSpecialMessageTypes(
            $messageThread,
            $fullBody,
            $inboxConfig,
            $webhookUrl,
            $color,
            $isForum,
            $isNewThread,
            $moderationReportId
        );

        $messageThread->title = $processedData->threadTitle;

        // Apply the "Open" tag to new report threads.
        $appliedTags = [];
        if ($moderationReportId !== null) {
            $appliedTags[] = self::DISCORD_TAG_MOD_REPORTS_OPEN;
        }

        $this->sendDiscordWebhooks(
            $processedData->webhookUrl,
            $userFrom,
            $userTo,
            $messageThread,
            $processedData->messageBody,
            $processedData->color,
            $processedData->isForum,
            $existingThreadId,
            $appliedTags,
        );
    }

    /**
     * Process special message types and determine routing.
     */
    private function processSpecialMessageTypes(
        MessageThread $messageThread,
        string $messageBody,
        array $inboxConfig,
        string $webhookUrl,
        int $color,
        bool $isForum,
        bool $isNewThread,
        ?int $moderationReportId = null,
    ): ProcessedDiscordMessageData {
        $messageTitle = mb_strtolower($messageThread->title);
        $threadTitle = $messageThread->title;

        // Detect report messages and route them to a special reports channel.
        if ($moderationReportId !== null && !empty($inboxConfig['reports_url'] ?? null)) {
            $webhookUrl = $inboxConfig['reports_url'];
            $color = self::COLOR_DEFAULT;
            $isForum = true;
        }

        // Detect Discord verification messages and route them to a channel for moderators.
        if (isset($inboxConfig['verify_url']) && $this->isVerificationMessage($messageTitle)) {
            $webhookUrl = $inboxConfig['verify_url'];
            $color = self::COLOR_VERIFICATION;
            $isForum = false;
        }

        // Detect manual unlock messages and route them to a special channel.
        if (isset($inboxConfig['manual_unlock_url']) && mb_strpos($messageTitle, 'manual') !== false) {
            $webhookUrl = $inboxConfig['manual_unlock_url'];
            $color = self::COLOR_MANUAL_UNLOCK;
            $isForum = false;
        }

        // These structured messages get routed to team-specific forum channels for
        // better organization, internal discussion, and tracking of achievement-related issues.
        $structuredTitlePrefixes = [
            'Incorrect type:' => 'incorrect_type_url',
            'Issue:' => 'achievement_issues_url',
            'Unwelcome Concept:' => 'unwelcome_concept_url',
            'Writing:' => 'url',
        ];

        foreach ($structuredTitlePrefixes as $prefix => $configKey) {
            if (mb_strpos($threadTitle, $prefix) !== false && isset($inboxConfig[$configKey])) {
                $webhookUrl = $inboxConfig[$configKey];
                $isForum = true;

                if (preg_match('/\[([0-9]+)\]/', $threadTitle, $matches)) {
                    $achievementId = $matches[1];

                    // Add the achievement URL only for new threads (the OP) to
                    // avoid duplicate links appearing in replies.
                    if ($isNewThread) {
                        $achievementUrl = route('achievement.show', $achievementId);
                        $messageBody = $achievementUrl . "\n\n" . $messageBody;
                    }

                    // Reformat title to put achievement ID first for easier scanning in forum view.
                    // This also can drive RABot behavior. Changing this format could be breaking!
                    if ($isNewThread && preg_match(
                            '/^(Incorrect type:|Issue:|Unwelcome Concept:|Writing:)\s*(.*)\s*\[([0-9]+)\]\s*(\(.*\))$/',
                            $threadTitle,
                            $titleMatches
                        )
                    ) {
                        $threadTitle = $achievementId . ': ' . trim($titleMatches[2]) . ' ' . $titleMatches[4];
                    }
                }

                break;
            }
        }

        return new ProcessedDiscordMessageData(
            color: $color,
            isForum: $isForum,
            messageBody: $messageBody,
            threadTitle: $threadTitle,
            webhookUrl: $webhookUrl,
        );
    }

    /**
     * Send the Discord webhooks, handling multi-part messages for forums.
     */
    private function sendDiscordWebhooks(
        string $webhookUrl,
        User $userFrom,
        User $userTo,
        MessageThread $messageThread,
        string $messageBody,
        int $color,
        bool $isForum,
        ?string $existingThreadId = null,
        array $appliedTags = [],
    ): void {
        if ($isForum) {
            $isNewThread = !$existingThreadId;

            $discordThreadId = $existingThreadId ?? $this->createDiscordThread(
                $webhookUrl,
                $userFrom,
                $userTo,
                $messageThread,
                $messageBody,
                $color,
                $appliedTags,
            );

            if ($discordThreadId) {
                // Always create a mapping for this message thread, whether the Discord
                // thread is new or reused. This ensures replies to any report conversation
                // get correctly forwarded to Discord.
                DiscordMessageThreadMapping::storeMapping(
                    $messageThread->id,
                    $discordThreadId
                );

                $this->sendMessagesToDiscordThread(
                    $webhookUrl,
                    $discordThreadId,
                    $userFrom,
                    $userTo,
                    $messageThread,
                    $messageBody,
                    $color,
                    $isNewThread
                );
            }
        } else {
            $this->sendSingleDiscordMessage(
                $webhookUrl,
                $userFrom,
                $userTo,
                $messageThread,
                mb_substr($messageBody, 0, self::DISCORD_EMBED_DESCRIPTION_LIMIT),
                $color
            );
        }
    }

    /**
     * Check if we have an existing Discord thread for this message thread.
     * If we do, we'll attach replies to the existing thread rather than making new threads.
     */
    private function getExistingDiscordThreadId(MessageThread $messageThread): ?string
    {
        $mapping = DiscordMessageThreadMapping::findMapping($messageThread->id);

        return $mapping?->discord_thread_id;
    }

    /**
     * Create a new Discord forum thread.
     */
    private function createDiscordThread(
        string $webhookUrl,
        User $userFrom,
        User $userTo,
        MessageThread $messageThread,
        string $messageBody,
        int $color,
        array $appliedTags = [],
    ): ?string {
        $isLongMessage = mb_strlen($messageBody) > self::DISCORD_EMBED_DESCRIPTION_LIMIT;
        $firstChunk = $isLongMessage
            ? mb_substr($messageBody, 0, self::DISCORD_EMBED_DESCRIPTION_LIMIT)
            : $messageBody;

        $payload = $this->buildDiscordPayload(
            $userFrom,
            $userTo,
            $messageThread,
            $firstChunk,
            $color,
            true
        );
        $payload['thread_name'] = mb_substr($messageThread->title, 0, self::DISCORD_THREAD_NAME_LIMIT);

        if (!empty($appliedTags)) {
            $payload['applied_tags'] = $appliedTags;
        }

        if ($isLongMessage) {
            $totalParts = count(mb_str_split($messageBody, self::DISCORD_EMBED_DESCRIPTION_LIMIT));
            $payload['content'] = "[Part 1 of {$totalParts}]";
        }

        // wait=true is required for Discord to give us the thread ID in the response.
        $response = $this->client->post($webhookUrl . '?wait=true', ['json' => $payload]);
        $responseData = json_decode($response->getBody()->getContents(), true);

        return $responseData['channel_id'] ?? null;
    }

    /**
     * Send messages to an existing Discord thread.
     * This only works if we've previously stored the thread ID in our mapping table.
     */
    private function sendMessagesToDiscordThread(
        string $webhookUrl,
        string $discordThreadId,
        User $userFrom,
        User $userTo,
        MessageThread $messageThread,
        string $messageBody,
        int $color,
        bool $isNewThread = false,
    ): void {
        $threadWebhookUrl = $webhookUrl . '?thread_id=' . $discordThreadId;

        if (mb_strlen($messageBody) > self::DISCORD_EMBED_DESCRIPTION_LIMIT) {
            $this->sendChunkedMessages(
                $threadWebhookUrl,
                $userFrom,
                $userTo,
                $messageThread,
                $messageBody,
                $color,
                $isNewThread
            );
        } else {
            // Skip sending for new threads since the first message was already sent during thread creation.
            if (!$isNewThread) {
                $payload = $this->buildDiscordPayload(
                    $userFrom,
                    $userTo,
                    $messageThread,
                    $messageBody,
                    $color,
                    true
                );

                $this->client->post($threadWebhookUrl, ['json' => $payload]);
            }
        }
    }

    /**
     * Send chunked messages to a Discord thread.
     * Discord has a max character limit which is much shorter than our own.
     * To get around this, we chunk messages from RA into a size Discord is happy with
     * and send them sequentially to the team inbox with multiple webhook calls.
     */
    private function sendChunkedMessages(
        string $threadWebhookUrl,
        User $userFrom,
        User $userTo,
        MessageThread $messageThread,
        string $messageBody,
        int $color,
        bool $skipFirstChunk = false,
    ): void {
        $chunks = mb_str_split($messageBody, self::DISCORD_EMBED_DESCRIPTION_LIMIT);
        $totalParts = count($chunks);
        $startIndex = $skipFirstChunk ? 1 : 0;

        for ($i = $startIndex; $i < $totalParts; $i++) {
            $partNumber = $i + 1;

            $payload = $this->buildDiscordPayload(
                $userFrom,
                $userTo,
                $messageThread,
                $chunks[$i],
                $color,
                $i === $startIndex
            );

            if ($totalParts > 1) {
                $payload['content'] = "[Part {$partNumber} of {$totalParts}]";
            }

            $this->client->post($threadWebhookUrl, ['json' => $payload]);

            // Use a naive delay to prevent Discord from deciding to randomly reorder messages.
            if ($i < $totalParts - 1) {
                usleep(self::CHUNK_SEND_DELAY_MICROSECONDS);
            }
        }
    }

    /**
     * Send a single Discord message (for non-forum channels).
     */
    private function sendSingleDiscordMessage(
        string $webhookUrl,
        User $userFrom,
        User $userTo,
        MessageThread $messageThread,
        string $messageBody,
        int $color,
    ): void {
        $payload = $this->buildDiscordPayload(
            $userFrom,
            $userTo,
            $messageThread,
            $messageBody,
            $color,
            true
        );

        $this->client->post($webhookUrl, ['json' => $payload]);
    }

    /**
     * Build the base Discord webhook payload.
     * This is what their webhook API expects. `color` is hex.
     */
    private function buildDiscordPayload(
        User $userFrom,
        User $userTo,
        MessageThread $messageThread,
        string $description,
        int $color,
        bool $includeAuthor = true,
    ): array {
        $embed = [
            'description' => $description,
            'color' => $color,
        ];

        if ($includeAuthor) {
            $embed['author'] = [
                'name' => $userFrom->display_name,
                'url' => route('user.show', ['user' => $userFrom]),
                'icon_url' => $userFrom->avatar_url,
            ];
            $embed['title'] = mb_substr($messageThread->title, 0, self::DISCORD_THREAD_NAME_LIMIT);
            $embed['url'] = route('message-thread.show', ['messageThread' => $messageThread->id]);
        }

        return [
            'username' => $userTo->username . ' Inbox',
            'avatar_url' => $userTo->avatar_url,
            'embeds' => [$embed],
        ];
    }

    /**
     * Forward a team account's reply to an existing Discord thread.
     */
    private function forwardTeamReplyToDiscord(
        array $senderInboxConfig,
        string $discordThreadId,
        User $userFrom,
        MessageThread $messageThread,
        Message $message,
    ): void {
        // Check if this thread is associated with a moderation report.
        // If so, we need to use reports_url to post to the reports forum.
        $isReportThread = UserModerationReport::where('message_thread_id', $messageThread->id)->exists();

        $webhookUrl = '';
        if ($isReportThread && !empty($senderInboxConfig['reports_url'])) {
            $webhookUrl = $senderInboxConfig['reports_url'];
        } else {
            foreach (['url', 'reports_url', 'verify_url', 'manual_unlock_url'] as $key) {
                if (!empty($senderInboxConfig[$key])) {
                    $webhookUrl = $senderInboxConfig[$key];
                    break;
                }
            }
        }

        if (empty($webhookUrl)) {
            return;
        }

        $fullBody = Shortcode::convertToMarkdown($message->body, self::MESSAGE_BODY_MAX_LENGTH, preserveWhitespace: true);

        if (empty($fullBody)) {
            return;
        }

        $this->sendMessagesToDiscordThread(
            $webhookUrl,
            $discordThreadId,
            $userFrom,
            $userFrom, // Pass the team account (sender) as both from and to so the webhook shows "[Team] Inbox"
            $messageThread,
            $fullBody,
            self::COLOR_DEFAULT,
            false
        );
    }

    /**
     * Check if a message title indicates a verification-related message.
     * These get routed to a special inbox for moderators.
     */
    private function isVerificationMessage(string $messageTitle): bool
    {
        $verificationKeywords = [
            'discord',
            'verification',
            'verified',
            'verify',
            'verifying',
        ];

        foreach ($verificationKeywords as $keyword) {
            if (mb_strpos($messageTitle, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }
}
