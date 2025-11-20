<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\ArticleType;
use App\Community\Enums\DiscordReportableType;
use App\Models\Achievement;
use App\Models\Comment;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\User;
use App\Support\Shortcode\Shortcode;

class BuildReportContextAction
{
    /** Discord message processing limits */
    private const MESSAGE_BODY_MAX_LENGTH = 10_000;

    /**
     * Prepend contextual information to report messages.
     * This adds link, author, and timestamp automatically.
     * For Discord, it also includes a content excerpt.
     */
    public function execute(
        string $messageBody,
        DiscordReportableType $reportableType,
        int $reportableId,
        bool $forDiscord = false,
    ): string {
        $reportedItem = $reportableType->getReportedItem($reportableId);
        if (!$reportedItem) {
            return $messageBody;
        }

        $context = '';

        // For DirectMessages in the user inbox, we link to the message since the reporter can access it.
        // For Discord, we skip the link since moderators can't access user inboxes.
        if ($reportableType !== DiscordReportableType::DirectMessage) {
            $context .= $forDiscord ? "**Reported Content:**\n" : "[b]Reported Content:[/b]\n";

            // Add a direct link to the reported content.
            $link = match ($reportableType) {
                DiscordReportableType::Comment => $reportedItem instanceof Comment
                    ? $this->buildCommentLink($reportedItem)
                    : null,
                DiscordReportableType::ForumTopicComment => $reportedItem->forumTopic
                    ? route('forum-topic.show', ['topic' => $reportedItem->forumTopic->id]) . '?comment=' . $reportedItem->id
                    : null,
                // TODO DiscordReportableType::UserProfile
                default => null,
            };

            if ($link) {
                $context .= $forDiscord ? $link . "\n" : "[url={$link}]{$link}[/url]\n";
            }
        } elseif (!$forDiscord) {
            // For DirectMessages in the user inbox, add a link the reporter can use.
            $link = route('message-thread.show', ['messageThread' => $reportedItem->thread_id]) . '?message=' . $reportedItem->id;
            $context .= "[b]Reported Content:[/b] [url={$link}]View reported message[/url]\n";
        }

        // Add the content author with appropriate formatting.
        $author = $reportedItem->user ?? $reportedItem->author ?? null;
        if ($author) {
            if ($forDiscord) {
                $authorUrl = route('user.show', ['user' => $author]);
                $context .= "**Author:** [{$author->display_name}]({$authorUrl})\n";
            } else {
                $context .= "[b]Author:[/b] [user={$author->id}]\n";
            }
        }

        // Add timestamp with appropriate formatting.
        $createdAt = $reportedItem->created_at ?? $reportedItem->Submitted ?? null;
        if ($createdAt) {
            $timeLabel = $reportableType === DiscordReportableType::DirectMessage ? 'Sent' : 'Posted';

            if ($forDiscord) {
                $timestamp = $createdAt->timestamp;
                $context .= "**{$timeLabel}:** <t:{$timestamp}:R>\n";
            } else {
                $formattedDate = $createdAt->format('Y-m-d H:i:s');
                $context .= "[b]{$timeLabel}:[/b] {$formattedDate}\n";
            }
        }

        // Add a content excerpt only for Discord (not for the user's inbox).
        if ($forDiscord) {
            $content = $reportedItem->body ?? $reportedItem->Payload ?? '';
            if ($content) {
                // For DirectMessage and Comment, include the FULL content.
                if ($reportableType === DiscordReportableType::DirectMessage) {
                    $context .= "**Full Message:**\n" . Shortcode::convertToMarkdown($content, self::MESSAGE_BODY_MAX_LENGTH, preserveWhitespace: true) . "\n";
                } elseif ($reportableType === DiscordReportableType::Comment) {
                    $context .= "**Full Comment:**\n" . Shortcode::convertToMarkdown($content, self::MESSAGE_BODY_MAX_LENGTH, preserveWhitespace: true) . "\n";
                } else {
                    // For other types, just include an excerpt.
                    $excerpt = mb_substr(Shortcode::convertToMarkdown($content, 200, preserveWhitespace: true), 0, 200);
                    $context .= "**Excerpt:** " . $excerpt . "...\n";
                }
            }
        }

        $context .= $forDiscord ? "\n**Report Details:**\n" : "\n[b]Report Details:[/b]\n";

        return $context . $messageBody;
    }

    /**
     * Build a link to a comment based on its ArticleType.
     * Handles all comment types: user walls, game walls, achievement walls, etc.
     */
    private function buildCommentLink(Comment $comment): ?string
    {
        if (!$comment->ArticleID) {
            return null;
        }

        $anchor = '#comment_' . $comment->ID;

        return match ($comment->ArticleType) {
            ArticleType::Game => ($game = Game::find($comment->ArticleID))
                ? route('game.show', ['game' => $game, 'tab' => 'community']) . $anchor
                : null,

            ArticleType::Achievement => ($achievement = Achievement::find($comment->ArticleID))
                ? route('achievement.show', $achievement) . $anchor
                : null,

            ArticleType::User, ArticleType::UserModeration => ($user = User::find($comment->ArticleID))
                ? route('user.show', $user) . $anchor
                : null,

            ArticleType::Leaderboard => ($leaderboard = Leaderboard::find($comment->ArticleID))
                ? route('leaderboard.show', $leaderboard) . $anchor
                : null,

            ArticleType::AchievementTicket => route('ticket.show', ['ticket' => $comment->ArticleID]) . $anchor,

            default => null,
        };
    }
}
