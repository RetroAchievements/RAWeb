<?php

declare(strict_types=1);

namespace App\Notifications\Ticket;

use App\Enums\UserPreference;
use App\Mail\Services\UnsubscribeService;
use App\Models\Achievement;
use App\Models\Leaderboard;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Ticket $ticket,
        private User $updatedBy,
        private string $newStatus,
        private string $comment,
    ) {
    }

    /**
     * @return string[]
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $unsubscribeService = app(UnsubscribeService::class);
        $categoryUrl = $unsubscribeService->generateCategoryUrl(
            $notifiable,
            UserPreference::EmailOn_TicketActivity
        );

        /**
         * TODO For now, we only support achievement tickets.
         * When leaderboard tickets are implemented, this will need to be updated.
         */
        /** @var Achievement|Leaderboard|null $ticketable */
        $ticketable = $this->ticket->achievement;

        return (new MailMessage())
            ->subject('Ticket status changed')
            ->markdown('mail.ticket.status-updated', [
                'ticket' => $this->ticket,
                'updatedBy' => $this->updatedBy,
                'newStatus' => $this->newStatus,
                'comment' => $this->comment,
                'ticketUrl' => route('ticket.show', ['ticket' => $this->ticket->id]),
                'ticketable' => $ticketable,
                'game' => $ticketable?->game,
                'categoryUrl' => $categoryUrl,
                'categoryText' => 'Unsubscribe from all ticket emails',
            ])
            ->withSymfonyMessage(function ($message) use ($categoryUrl) {
                $message->getHeaders()->addTextHeader('List-Unsubscribe', "<{$categoryUrl}>");
                $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
    }
}
