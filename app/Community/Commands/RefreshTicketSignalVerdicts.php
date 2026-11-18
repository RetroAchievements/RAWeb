<?php

declare(strict_types=1);

namespace App\Community\Commands;

use App\Community\Enums\TicketState;
use App\Models\Ticket;
use App\Platform\Actions\RefreshTicketSignalVerdictsAction;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class RefreshTicketSignalVerdicts extends Command
{
    protected $signature = 'ra:community:ticket:refresh-signal-verdicts';
    protected $description = 'Recalculates the stored signal verdict of every unresolved ticket';

    private const TICKETS_PER_CHUNK = 100;

    public function handle(): void
    {
        $action = new RefreshTicketSignalVerdictsAction();

        $unresolvedTickets = Ticket::query()->whereNotIn('state', [TicketState::Resolved, TicketState::Closed]);

        $progress = $this->output->createProgressBar($unresolvedTickets->count());

        $unresolvedTickets->chunkById(
            self::TICKETS_PER_CHUNK,
            function (Collection $ticketChunk) use ($action, $progress): void {
                $action->execute($ticketChunk);
                $progress->advance($ticketChunk->count());
            },
        );

        $progress->finish();
        $this->newLine();
    }
}
