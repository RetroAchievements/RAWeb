<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Ticket\TicketClaimChangedNotification;
use App\Platform\Enums\TicketChangelogEntryType;

class ReleaseTicketClaimAction
{
    public function execute(Ticket $ticket, User $actor, bool $isExpired = false): void
    {
        $claimant = $ticket->assignee;
        $maintainer = $ticket->getTicketableModel()->getTicketableAssignee();

        $ticket->assignee_id = $maintainer?->id;
        $ticket->save();

        $auditEvent = $isExpired ? Ticket::CLAIM_EXPIRED_EVENT : Ticket::CLAIM_RELEASED_EVENT;

        activity()
            ->causedBy($actor)
            ->performedOn($ticket)
            ->event($auditEvent)
            ->log($auditEvent);

        if ($maintainer && $claimant) {
            $changeType = $isExpired ? TicketChangelogEntryType::ClaimExpired : TicketChangelogEntryType::ClaimReleased;
            $maintainer->notify(new TicketClaimChangedNotification($ticket, $claimant, $changeType));
        }
    }
}
