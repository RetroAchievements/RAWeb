<?php

declare(strict_types=1);

namespace App\Mail;

use App\Community\Enums\CommentableType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Enums\UserPreference;
use App\Mail\Services\UnsubscribeService;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class CommunityActivityMail extends Mailable
{
    use Queueable; use SerializesModels;

    public ?string $granularUrl = null;
    public ?string $granularText = null;
    public string $categoryUrl;
    public string $categoryText;
    public Achievement|Leaderboard|null $ticketable = null;
    public ?Game $game = null;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $toUser,
        public int $activityId,
        public string $activityCommenterDisplayName,
        public CommentableType $commentableType,
        public string $articleTitle,
        public string $urlTarget,
        public bool $isThreadInvolved = false,
        public ?string $payload = null,
    ) {
        $this->setupUnsubscribeLinks();

        // If this is a ticket comment, fetch the ticket data.
        if ($this->commentableType === CommentableType::AchievementTicket) {
            $ticket = Ticket::with(['achievement.game.system'])->find($this->activityId);
            if ($ticket) {
                $this->ticketable = $ticket->achievement;
                $this->game = $ticket->achievement?->game;
            }
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->buildEmailSubject(
                $this->commentableType,
                $this->activityCommenterDisplayName
            ),
        );
    }

    /**
     * Get the message headers.
     */
    public function headers(): Headers
    {
        $unsubscribeUrl = $this->granularUrl ?? $this->categoryUrl;

        return new Headers(
            text: [
                'List-Unsubscribe' => "<{$unsubscribeUrl}>",
                'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
            ],
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.community.activity',
            with: [
                'activityDescription' => $this->buildActivityDescription(
                    $this->commentableType,
                    $this->articleTitle,
                    $this->toUser->display_name,
                    $this->isThreadInvolved,
                ),
                'toUserDisplayName' => $this->toUser->display_name,
                'granularUrl' => $this->granularUrl,
                'granularText' => $this->granularText,
                'categoryUrl' => $this->categoryUrl,
                'categoryText' => $this->categoryText,
                'ticketable' => $this->ticketable,
                'game' => $this->game,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    private function buildActivityDescription(
        CommentableType $commentableType,
        string $articleTitle,
        string $toUserDisplayName,
        bool $isThreadInvolved,
    ): string {
        return match ($commentableType) {
            CommentableType::Game => "the game wall for {$articleTitle}",
            CommentableType::Achievement => "the achievement wall for {$articleTitle}",
            CommentableType::User => $articleTitle === $toUserDisplayName
                ? "your user wall"
                : "{$articleTitle}'s user wall",
            CommentableType::Leaderboard => "the leaderboard wall for {$articleTitle}",
            CommentableType::Forum => "the forum thread \"{$articleTitle}\"",
            CommentableType::AchievementTicket => $isThreadInvolved
                ? "a ticket you're subscribed to"
                : "the ticket you reported",
            default => $isThreadInvolved ? "a thread you've commented in" : "your latest activity",
        };
    }

    private function buildEmailSubject(CommentableType $commentableType, string $activityCommenterDisplayName): string
    {
        return match ($commentableType) {
            CommentableType::Game => "New Game Wall Comment from {$activityCommenterDisplayName}",
            CommentableType::Achievement => "New Achievement Comment from {$activityCommenterDisplayName}",
            CommentableType::User => "New User Wall Comment from {$activityCommenterDisplayName}",
            CommentableType::Leaderboard => "New Leaderboard Comment from {$activityCommenterDisplayName}",
            CommentableType::Forum => "New Forum Comment from {$activityCommenterDisplayName}",
            CommentableType::AchievementTicket => "New Ticket Comment from {$activityCommenterDisplayName}",
            default => "New Activity Comment from {$activityCommenterDisplayName}",
        };
    }

    private function setupUnsubscribeLinks(): void
    {
        $unsubscribeService = app(UnsubscribeService::class);

        switch ($this->commentableType) {
            case CommentableType::Game:
                $this->granularUrl = $unsubscribeService->generateGranularUrl(
                    $this->toUser,
                    SubscriptionSubjectType::GameWall,
                    $this->activityId
                );
                $this->granularText = 'Unsubscribe from this game wall';

                $this->categoryUrl = $unsubscribeService->generateCategoryUrl(
                    $this->toUser,
                    UserPreference::EmailOn_AchievementComment
                );
                $this->categoryText = 'Unsubscribe from all wall comment emails';
                break;

            case CommentableType::Achievement:
                $this->granularUrl = $unsubscribeService->generateGranularUrl(
                    $this->toUser,
                    SubscriptionSubjectType::Achievement,
                    $this->activityId
                );
                $this->granularText = 'Unsubscribe from this achievement';

                $this->categoryUrl = $unsubscribeService->generateCategoryUrl(
                    $this->toUser,
                    UserPreference::EmailOn_AchievementComment
                );
                $this->categoryText = 'Unsubscribe from all achievement comment emails';
                break;

            case CommentableType::User:
                $this->granularUrl = $unsubscribeService->generateGranularUrl(
                    $this->toUser,
                    SubscriptionSubjectType::UserWall,
                    $this->activityId
                );
                $this->granularText = 'Unsubscribe from this user wall';

                $this->categoryUrl = $unsubscribeService->generateCategoryUrl(
                    $this->toUser,
                    UserPreference::EmailOn_UserWallComment
                );
                $this->categoryText = 'Unsubscribe from all user wall comment emails';
                break;

            case CommentableType::Forum:
                $this->granularUrl = $unsubscribeService->generateGranularUrl(
                    $this->toUser,
                    SubscriptionSubjectType::ForumTopic,
                    $this->activityId
                );
                $this->granularText = 'Unsubscribe from this forum thread';

                $this->categoryUrl = $unsubscribeService->generateCategoryUrl(
                    $this->toUser,
                    UserPreference::EmailOn_ForumReply
                );
                $this->categoryText = 'Unsubscribe from all forum reply emails';
                break;

            case CommentableType::AchievementTicket:
                $this->categoryUrl = $unsubscribeService->generateCategoryUrl(
                    $this->toUser,
                    UserPreference::EmailOn_TicketActivity
                );
                $this->categoryText = 'Unsubscribe from all ticket activity emails';
                break;

            default:
                $this->categoryUrl = $unsubscribeService->generateCategoryUrl(
                    $this->toUser,
                    UserPreference::EmailOn_ActivityComment
                );
                $this->categoryText = 'Unsubscribe from all activity comment emails';
                break;
        }
    }
}
