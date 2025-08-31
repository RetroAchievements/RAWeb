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
use Illuminate\Queue\SerializesModels;

class CommunityFriendMail extends Mailable
{
    use Queueable; use SerializesModels;

    public string $categoryUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $toUser,
        public User $fromUser,
    ) {
        $unsubscribeService = app(UnsubscribeService::class);

        $this->categoryUrl = $unsubscribeService->generateCategoryUrl(
            $this->toUser,
            UserPreference::EmailOn_Followed
        );
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->fromUser->display_name} is now following you",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.community.friend',
            with: [
                'categoryUrl' => $this->categoryUrl,
                'categoryText' => 'Unsubscribe from follower notification emails',
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
