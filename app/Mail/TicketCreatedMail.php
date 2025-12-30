<?php

declare(strict_types=1);

namespace App\Mail;

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

class TicketCreatedMail extends Mailable
{
    use Queueable; use SerializesModels;

    public string $granularUrl;
    public string $categoryUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public Ticket $ticket,
        public Game $game,
        public Achievement|Leaderboard $ticketable,
        public bool $isMaintainer = false,
    ) {
        $unsubscribeService = app(UnsubscribeService::class);

        $this->granularUrl = $unsubscribeService->generateGranularUrl(
            $this->user,
            SubscriptionSubjectType::GameTickets,
            $this->game->id
        );

        $this->categoryUrl = $unsubscribeService->generateCategoryUrl(
            $this->user,
            UserPreference::EmailOn_TicketActivity
        );
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $type = $this->ticketable instanceof Achievement ? 'Achievement' : 'Leaderboard';

        return new Envelope(
            subject: "{$type} Bug Report ({$this->game->title})",
        );
    }

    /**
     * Get the message headers.
     */
    public function headers(): Headers
    {
        return new Headers(
            text: [
                'List-Unsubscribe' => "<{$this->granularUrl}>",
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
            markdown: 'mail.ticket.created',
            with: [
                'problemType' => $this->ticket->type->label(),
                'ticketUrl' => route('ticket.show', ['ticket' => $this->ticket->id]),
                'granularUrl' => $this->granularUrl,
                'granularText' => 'Unsubscribe from tickets for this game',
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
