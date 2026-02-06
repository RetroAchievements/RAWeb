<?php

declare(strict_types=1);

namespace App\Notifications\Ticket;

use App\Community\Enums\SubscriptionSubjectType;
use App\Enums\UserPreference;
use App\Mail\Services\UnsubscribeService;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Ticket $ticket,
        private Game $game,
        private Achievement|Leaderboard $ticketable,
        private bool $isMaintainer = false,
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

        $granularUrl = $unsubscribeService->generateGranularUrl(
            $notifiable,
            SubscriptionSubjectType::GameTickets,
            $this->game->id
        );

        $categoryUrl = $unsubscribeService->generateCategoryUrl(
            $notifiable,
            UserPreference::EmailOn_TicketActivity
        );

        return (new MailMessage())
            ->subject(($this->ticketable instanceof Achievement ? 'Achievement' : 'Leaderboard') . " Bug Report ({$this->game->title})")
            ->markdown('mail.ticket.created', [
                'user' => $notifiable,
                'ticket' => $this->ticket,
                'game' => $this->game,
                'ticketable' => $this->ticketable,
                'isMaintainer' => $this->isMaintainer,
                'problemType' => $this->ticket->type->label(),
                'ticketUrl' => route('ticket.show', ['ticket' => $this->ticket->id]),
                'granularUrl' => $granularUrl,
                'granularText' => 'Unsubscribe from tickets for this game',
                'categoryUrl' => $categoryUrl,
                'categoryText' => 'Unsubscribe from all ticket emails',
            ])
            ->withSymfonyMessage(function ($message) use ($granularUrl) {
                $message->getHeaders()->addTextHeader('List-Unsubscribe', "<{$granularUrl}>");
                $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
    }
}
