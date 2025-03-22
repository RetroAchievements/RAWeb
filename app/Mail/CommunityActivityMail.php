<?php

declare(strict_types=1);

namespace App\Mail;

use App\Community\Enums\ArticleType;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommunityActivityMail extends Mailable
{
    use Queueable; use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $toUserDisplayName,
        public int $activityId,
        public string $activityCommenterDisplayName,
        public int $articleType,
        public string $articleTitle,
        public string $urlTarget,
        public bool $isThreadInvolved = false,
        public ?string $payload = null,
    ) {
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
                    $this->toUserDisplayName,
                    $this->isThreadInvolved,
                ),
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
        bool $isThreadInvolved
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
                $activityDescription = "the ticket you reported for {$articleTitle}";
                if ($isThreadInvolved) {
                    $activityDescription = "a ticket for {$articleTitle}";
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
}
