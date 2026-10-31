<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;

/**
 * Persists each ticket's current signal verdict so they can be queried by and filtered upon.
 */
class RefreshTicketSignalVerdictsAction
{
    /**
     * @param Collection<int, Ticket> $tickets
     */
    public function execute(Collection $tickets): void
    {
        $signalsForTickets = (new BuildTicketSignalsAction())->execute($tickets);

        // Recalculated verdicts aren't really edits to the ticket. Don't touch updated_at.
        Ticket::withoutTimestamps(function () use ($tickets, $signalsForTickets) {
            foreach ($tickets as $ticket) {
                $ticket->signal_verdict = $signalsForTickets[$ticket->id]?->verdict;
                $ticket->save();
            }
        });
    }
}
