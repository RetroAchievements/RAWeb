<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Ticket;
use App\Platform\Services\GameOpenTicketCountService;

class TicketObserver
{
    public function __construct(
        private readonly GameOpenTicketCountService $gameOpenTicketCountService,
    ) {
    }

    public function created(Ticket $ticket): void
    {
        $this->gameOpenTicketCountService->clearForTicket($ticket);
    }

    public function updated(Ticket $ticket): void
    {
        if (!$ticket->wasChanged(['state', 'ticketable_type', 'ticketable_id'])) {
            return;
        }

        $this->gameOpenTicketCountService->clearForTicket($ticket);

        if ($ticket->wasChanged(['ticketable_type', 'ticketable_id'])) {
            $this->gameOpenTicketCountService->clearForTicketable(
                $ticket->getOriginal('ticketable_type'),
                $ticket->getOriginal('ticketable_id') !== null ? (int) $ticket->getOriginal('ticketable_id') : null,
            );
        }
    }

    public function deleted(Ticket $ticket): void
    {
        $this->gameOpenTicketCountService->clearForTicket($ticket);
    }

    public function restored(Ticket $ticket): void
    {
        $this->gameOpenTicketCountService->clearForTicket($ticket);
    }

    public function forceDeleted(Ticket $ticket): void
    {
        $this->gameOpenTicketCountService->clearForTicket($ticket);
    }
}
