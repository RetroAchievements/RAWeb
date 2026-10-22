<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\TicketAction;
use App\Community\Enums\TicketState;
use App\Models\Ticket;
use App\Models\User;

class ReopenTicketOnReporterReplyAction
{
    public function __construct(
        private readonly ChangeTicketStateAction $changeTicketStateAction,
    ) {
    }

    public function execute(Ticket $ticket, User $user): void
    {
        if (
            $ticket->state === TicketState::Request
            && $ticket->reporter_id === $user->id
            && $user->can('updateState', [$ticket, TicketAction::Reopen])
        ) {
            $this->changeTicketStateAction->execute($ticket, TicketAction::Reopen, $user);
        }
    }
}
