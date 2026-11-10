<?php

declare(strict_types=1);

namespace App\Notifications\Ticket;

use App\Enums\UserPreference;
use App\Mail\Services\UnsubscribeService;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Enums\TicketChangelogEntryType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketClaimChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Ticket $ticket,
        private User $claimant,
        private TicketChangelogEntryType $change,
    ) {
    }

    /**
     * @return string[]
     */
    public function via(User $notifiable): array
    {
        if (!$this->ticket->ticketable) {
            return [];
        }

        // Don't notify non-devs.
        if (!$notifiable->hasAnyRole([Role::DEVELOPER, Role::DEVELOPER_JUNIOR])) {
            return [];
        }

        if (!BitSet($notifiable->preferences_bitfield, UserPreference::EmailOn_TicketActivity)) {
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
            ->subject($this->change === TicketChangelogEntryType::Claimed ? 'A developer claimed one of your tickets' : 'A ticket was reassigned back to you')
            ->markdown('mail.ticket.claim-changed', [
                'ticket' => $this->ticket,
                'ticketable' => $ticketableRecord,
                'game' => $ticketableRecord->getTicketableGame(),
                'maintainer' => $notifiable,
                'claimant' => $this->claimant,
                'change' => $this->change,
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
