<?php

declare(strict_types=1);

namespace App\Mail;

use App\Community\Enums\TicketType;
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

class TicketCreatedMail extends Mailable
{
    use Queueable; use SerializesModels;

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
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'mail.ticket.created',
            with: [
                'problemType' => TicketType::toString($this->ticket->ReportType),
                'ticketUrl' => route('ticket.show', ['ticket' => $this->ticket->id]),
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
