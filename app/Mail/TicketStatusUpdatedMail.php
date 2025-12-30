<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\UserPreference;
use App\Mail\Services\UnsubscribeService;
use App\Models\Achievement;
use App\Models\Leaderboard;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class TicketStatusUpdatedMail extends Mailable
{
    use Queueable; use SerializesModels;

    public Achievement|Leaderboard|null $ticketable;
    public string $categoryUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Ticket $ticket,
        public User $updatedBy,
        public string $newStatus,
        public string $comment,
    ) {
        /**
         * TODO For now, we only support achievement tickets.
         * When leaderboard tickets are implemented, this will need to be updated.
         */
        $this->ticketable = $this->ticket->achievement;

        $unsubscribeService = app(UnsubscribeService::class);
        $this->categoryUrl = $unsubscribeService->generateCategoryUrl(
            $this->ticket->reporter,
            UserPreference::EmailOn_TicketActivity
        );
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ticket status changed',
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
            markdown: 'mail.ticket.status-updated',
            with: [
                'ticketUrl' => route('ticket.show', ['ticket' => $this->ticket->id]),
                'ticketable' => $this->ticketable,
                'game' => $this->ticketable->game,
                'categoryUrl' => $this->categoryUrl,
                'categoryText' => 'Unsubscribe from all ticket emails',
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
