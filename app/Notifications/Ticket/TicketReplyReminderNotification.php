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
use Illuminate\Support\Facades\URL;

class TicketReplyReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Ticket $ticket,
        private Carbon $closeAt,
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
        $categoryUrl = app(UnsubscribeService::class)->generateCategoryUrl(
            $notifiable,
            UserPreference::EmailOn_TicketActivity
        );

        $ticketableModel = $this->ticket->getTicketableModel();

        // We'll directly quote the person who last asked the reporter something.
        $latestQuestion = $this->ticket->comments()
            ->notAutomated()
            ->where('user_id', '!=', $this->ticket->reporter_id)
            ->with('user')
            ->latest()
            ->latest('id')
            ->first();

        return (new MailMessage())
            ->subject('A developer has a question about your ticket')
            ->markdown('mail.ticket.reply-reminder', [
                'ticket' => $this->ticket,
                'ticketable' => $ticketableModel,
                'game' => $ticketableModel->getTicketableGame(),
                'question' => $latestQuestion,
                'closeAt' => $this->closeAt,
                'replyUrl' => route('ticket.show', ['ticket' => $this->ticket->id]) . '#comments',
                'noLongerAProblemUrl' => URL::temporarySignedRoute('ticket.no-longer-a-problem', now()->addDays(30), ['ticket' => $this->ticket->id]),
                'categoryUrl' => $categoryUrl,
                'categoryText' => 'Unsubscribe from all ticket emails',
            ])
            ->withSymfonyMessage(function ($message) use ($categoryUrl) {
                $message->getHeaders()->addTextHeader('List-Unsubscribe', "<{$categoryUrl}>");
                $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
    }
}
