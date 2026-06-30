<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Ticket;
use App\Platform\Services\GameOpenTicketCountService;
use App\Platform\Services\UserTicketCountService;

class TicketObserver
{
    public function __construct(
        private readonly GameOpenTicketCountService $gameOpenTicketCountService,
        private readonly UserTicketCountService $userTicketCountService,
    ) {
    }

    public function created(Ticket $ticket): void
    {
        $this->gameOpenTicketCountService->clearForTicket($ticket);
        $this->clearForCurrentUsers($ticket);
    }

    public function updated(Ticket $ticket): void
    {
        if (!$ticket->wasChanged(['state', 'ticketable_type', 'ticketable_id', 'ticketable_author_id', 'reporter_id'])) {
            return;
        }

        $this->gameOpenTicketCountService->clearForTicket($ticket);

        if ($ticket->wasChanged(['ticketable_type', 'ticketable_id'])) {
            $this->gameOpenTicketCountService->clearForTicketable(
                $ticket->getOriginal('ticketable_type'),
                $ticket->getOriginal('ticketable_id') !== null ? (int) $ticket->getOriginal('ticketable_id') : null,
            );
        }

        $this->clearForCurrentUsers($ticket);

        // When the assignee or reporter changed, also bust the prior user's caches.
        if ($ticket->wasChanged('ticketable_author_id')) {
            $priorAuthorId = $this->priorUserId($ticket, 'ticketable_author_id');
            if ($priorAuthorId !== null) {
                $this->userTicketCountService->clearForUserId($priorAuthorId);
            }
        }
        if ($ticket->wasChanged('reporter_id')) {
            $priorReporterId = $this->priorUserId($ticket, 'reporter_id');
            if ($priorReporterId !== null) {
                $this->userTicketCountService->clearForUserId($priorReporterId);
            }
        }
    }

    public function deleted(Ticket $ticket): void
    {
        $this->gameOpenTicketCountService->clearForTicket($ticket);
        $this->clearForCurrentUsers($ticket);
    }

    public function restored(Ticket $ticket): void
    {
        $this->gameOpenTicketCountService->clearForTicket($ticket);
        $this->clearForCurrentUsers($ticket);
    }

    public function forceDeleted(Ticket $ticket): void
    {
        $this->gameOpenTicketCountService->clearForTicket($ticket);
        $this->clearForCurrentUsers($ticket);
    }

    private function clearForCurrentUsers(Ticket $ticket): void
    {
        if ($ticket->ticketable_author_id !== null) {
            $this->userTicketCountService->clearForUserId($ticket->ticketable_author_id);
        }
        if ($ticket->reporter_id !== null) {
            $this->userTicketCountService->clearForUserId($ticket->reporter_id);
        }
    }

    private function priorUserId(Ticket $ticket, string $attribute): ?int
    {
        $value = $ticket->getOriginal($attribute);

        return $value === null ? null : (int) $value;
    }
}
