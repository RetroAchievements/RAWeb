<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\UserPreference;
use App\Mail\Services\UnsubscribeService;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class DailyDigestMail extends Mailable
{
    use Queueable; use SerializesModels;

    public string $categoryUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public array $notificationItems,
    ) {
        $unsubscribeService = app(UnsubscribeService::class);

        $this->categoryUrl = $unsubscribeService->generateCategoryUrl(
            $this->user,
            UserPreference::EmailOff_DailyDigest
        );
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "RetroAchievements Conversations Summary",
        );
    }

    /**
     * Get the message headers.
     */
    public function headers(): Headers
    {
        return new Headers(
            text: [
                'List-Unsubscribe' => "<{$this->categoryUrl}>",
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
            markdown: 'mail.community.daily-digest',
            with: [
                'categoryUrl' => $this->categoryUrl,
                'categoryText' => 'Unsubscribe from daily digest emails',
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
}
