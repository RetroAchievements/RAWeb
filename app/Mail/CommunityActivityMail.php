<?php

declare(strict_types=1);

namespace App\Mail;

use App\Community\Enums\ArticleType;
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
        public int $articleType,
        public string $articleTitle,
        public string $urlTarget,
        public bool $isThreadInvolved = false,
        public ?string $payload = null,
    ) {
        $this->setupUnsubscribeLinks();

        // If this is a ticket comment, fetch the ticket data.
        if ($this->articleType === ArticleType::AchievementTicket) {
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
                $this->articleType,
                $this->activityCommenterDisplayName
            ),
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
                    $this->articleType,
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
        int $articleType,
        string $articleTitle,
        string $toUserDisplayName,
        bool $isThreadInvolved,
    ): string {
        $activityDescription = $isThreadInvolved ? "a thread you've commented in" : "your latest activity";

        switch ($articleType) {
            case ArticleType::Game:
                $activityDescription = "the game wall for {$articleTitle}";
                break;

            case ArticleType::Achievement:
                $activityDescription = "the achievement wall for {$articleTitle}";
                break;

            case ArticleType::User:
                $activityDescription = "your user wall";
                if ($articleTitle !== $toUserDisplayName) {
                    $activityDescription = "{$articleTitle}'s user wall";
                }
                break;

            case ArticleType::Leaderboard:
                $activityDescription = "the leaderboard wall for {$articleTitle}";
                break;

            case ArticleType::Forum:
                $activityDescription = "the forum thread \"{$articleTitle}\"";
                break;

            case ArticleType::AchievementTicket:
                $activityDescription = "the ticket you reported";
                if ($isThreadInvolved) {
                    $activityDescription = "a ticket you're subscribed to";
                }
                break;

            default:
                break;
        }

        return $activityDescription;
    }

    private function buildEmailSubject(int $articleType, string $activityCommenterDisplayName): string
    {
        $title = "New Activity Comment from {$activityCommenterDisplayName}";

        switch ($articleType) {
            case ArticleType::Game:
                $title = "New Game Wall Comment from {$activityCommenterDisplayName}";
                break;

            case ArticleType::Achievement:
                $title = "New Achievement Comment from {$activityCommenterDisplayName}";
                break;

            case ArticleType::User:
                $title = "New User Wall Comment from {$activityCommenterDisplayName}";
                break;

            case ArticleType::Leaderboard:
                $title = "New Leaderboard Comment from {$activityCommenterDisplayName}";
                break;

            case ArticleType::Forum:
                $title = "New Forum Comment from {$activityCommenterDisplayName}";
                break;

            case ArticleType::AchievementTicket:
                $title = "New Ticket Comment from {$activityCommenterDisplayName}";
                break;

            default:
                break;
        }

        return $title;
    }

    private function setupUnsubscribeLinks(): void
    {
        $unsubscribeService = app(UnsubscribeService::class);

        switch ($this->articleType) {
            case ArticleType::Game:
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

            case ArticleType::Achievement:
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

            case ArticleType::User:
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

            case ArticleType::Forum:
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

            case ArticleType::AchievementTicket:
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
