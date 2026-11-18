<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Community\Enums\TicketAction;
use App\Community\Enums\TicketState;
use App\Enums\UserPreference;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Ticket\TicketReplyReminderNotification;
use App\Platform\Actions\ChangeTicketStateAction;
use App\Platform\Actions\GetTicketAutoCloseScheduleAction;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CloseUnansweredTickets extends Command
{
    protected $signature = 'ra:community:ticket:close-unanswered';
    protected $description = 'Nudges reporters who have not answered a request for more info on a ticket. Closes the ticket when they do not respond.';

    public function __construct(
        private readonly ChangeTicketStateAction $changeTicketStateAction,
        private readonly GetTicketAutoCloseScheduleAction $getTicketAutoCloseScheduleAction,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $serverUser = User::whereName('Server')->firstOrFail();

        $allPendingTickets = Ticket::where('state', TicketState::Request)->with(['reporter', 'ticketable'])->get();

        foreach ($allPendingTickets as $pendingTicket) {
            $autoCloseSchedule = $this->getTicketAutoCloseScheduleAction->execute($pendingTicket);
            if (!$autoCloseSchedule) {
                continue;
            }

            if (!$autoCloseSchedule->isReminded) {
                if ($autoCloseSchedule->remindAt->isPast()) {
                    $this->remindReporter($pendingTicket, $serverUser, $autoCloseSchedule->closeAt);
                }

                continue;
            }

            if ($autoCloseSchedule->closeAt->isFuture()) {
                continue;
            }

            $this->changeTicketStateAction->execute($pendingTicket, TicketAction::NoReplyFromReporter, $serverUser);
        }
    }

    private function remindReporter(Ticket $ticket, User $serverUser, Carbon $closeAt): void
    {
        activity()
            ->causedBy($serverUser)
            ->performedOn($ticket)
            ->event(Ticket::REPORTER_REMINDED_EVENT)
            ->log(Ticket::REPORTER_REMINDED_EVENT);

        if (BitSet($ticket->reporter->preferences_bitfield, UserPreference::EmailOn_TicketActivity)) {
            $ticket->reporter->notify(new TicketReplyReminderNotification($ticket, $closeAt));
        }
    }
}
