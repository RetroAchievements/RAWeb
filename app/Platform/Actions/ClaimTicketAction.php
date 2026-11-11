<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Services\SubscriptionService;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\Ticket\TicketClaimChangedNotification;
use App\Platform\Enums\TicketChangelogEntryType;

class ClaimTicketAction
{
    public function execute(Ticket $ticket, User $developer): void
    {
        $ticket->assignee_id = $developer->id;
        $ticket->save();

        activity()
            ->causedBy($developer)
            ->performedOn($ticket)
            ->event(Ticket::CLAIMED_EVENT)
            ->log(Ticket::CLAIMED_EVENT);

        (new SubscriptionService())->updateSubscription($developer, SubscriptionSubjectType::Ticket, $ticket->id, true);

        $ticketMaintainer = $ticket->getTicketableModel()->getTicketableAssignee();
        if ($ticketMaintainer && !$ticketMaintainer->is($developer)) {
            $ticketMaintainer->notify(new TicketClaimChangedNotification($ticket, $developer, TicketChangelogEntryType::Claimed));
        }
    }
}
