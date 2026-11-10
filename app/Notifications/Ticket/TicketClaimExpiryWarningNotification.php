<?php

declare(strict_types=1);

namespace App\Notifications\Ticket;

use App\Enums\UserPreference;
use App\Mail\Services\UnsubscribeService;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class TicketClaimExpiryWarningNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Ticket $ticket,
        private Carbon $expiresAt,
    ) {
    }

    /**
     * @return string[]
     */
    public function via(object $notifiable): array
    {
        if (!$this->ticket->ticketable) {
            return [];
        }

        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $unsubscribeCategoryUrl = app(UnsubscribeService::class)->generateCategoryUrl(
            $notifiable,
            UserPreference::EmailOn_TicketActivity
        );

        $ticketableRecord = $this->ticket->getTicketableModel();

        return (new MailMessage())
            ->subject('Your ticket claim is about to expire')
            ->markdown('mail.ticket.claim-expiry-warning', [
                'ticket' => $this->ticket,
                'ticketable' => $ticketableRecord,
                'game' => $ticketableRecord->getTicketableGame(),
                'expiresAt' => $this->expiresAt,
                'ticketUrl' => route('ticket.show', ['ticket' => $this->ticket->id]),
                'categoryUrl' => $unsubscribeCategoryUrl,
                'categoryText' => 'Unsubscribe from all ticket emails',
            ])
            ->withSymfonyMessage(function ($symfonyMailMessage) use ($unsubscribeCategoryUrl) {
                $symfonyMailMessage->getHeaders()->addTextHeader('List-Unsubscribe', "<{$unsubscribeCategoryUrl}>");
                $symfonyMailMessage->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
    }
}
