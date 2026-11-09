<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\TicketState;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\Trigger;
use App\Platform\Data\TicketClaimStatusData;
use Illuminate\Support\Carbon;

class GetTicketClaimStatusAction
{
    /**
     * Data-driven. Devs who work on another person's tickets are responsive ~95.5% of the time within 14 days.
     */
    public const IDLE_DAYS = 14;

    private const NOTICE_DAYS_BEFORE_EXPIRY = 3;

    public function execute(Ticket $ticket): TicketClaimStatusData
    {
        $currentAssignee = $ticket->assignee;

        if (!$currentAssignee?->hasAnyRole([Role::DEVELOPER, Role::DEVELOPER_JUNIOR])) {
            return new TicketClaimStatusData(isClaimable: true, isWarned: false, warnAt: null, expiresAt: null);
        }

        // The clock pauses when the ticket is waiting on its reporter.
        if ($ticket->state !== TicketState::Open) {
            return new TicketClaimStatusData(isClaimable: false, isWarned: false, warnAt: null, expiresAt: null);
        }

        $lastActivityAt = $this->getLastAssigneeActivityAt($ticket);
        $isAssigneeIdle = $lastActivityAt->lte(now()->subDays(self::IDLE_DAYS));

        if (!$ticket->isClaimed()) {
            return new TicketClaimStatusData(isClaimable: $isAssigneeIdle, isWarned: false, warnAt: null, expiresAt: null);
        }

        $newestWarningAt = $ticket->auditLog()->where('event', Ticket::CLAIM_EXPIRY_WARNED_EVENT)->max('created_at');
        $hasBeenWarned = $newestWarningAt && Carbon::parse($newestWarningAt)->gte($lastActivityAt);

        $warningAt = $hasBeenWarned
            ? Carbon::parse($newestWarningAt)
            : $lastActivityAt->copy()->addDays(self::IDLE_DAYS - self::NOTICE_DAYS_BEFORE_EXPIRY)->endOfDay();

        $expiryCountdownStartAt = $hasBeenWarned ? $warningAt->copy() : $warningAt->copy()->max(now());

        return new TicketClaimStatusData(
            isClaimable: $isAssigneeIdle,
            isWarned: $hasBeenWarned,
            warnAt: $warningAt,
            expiresAt: $expiryCountdownStartAt->addDays(self::NOTICE_DAYS_BEFORE_EXPIRY)->endOfDay(),
        );
    }

    private function getLastAssigneeActivityAt(Ticket $ticket): Carbon
    {
        $activityTimestamps = [
            $ticket->created_at,
            $ticket->comments()->where('user_id', $ticket->assignee_id)->max('created_at'),
            $ticket->auditLog()->where('causer_id', $ticket->assignee_id)->max('created_at'),
            // Every return to Open restarts the clock.
            $ticket->auditLog()
                ->where('event', 'updated')
                ->where('properties->attributes->state', TicketState::Open->value)
                ->max('created_at'),
            Trigger::query()
                ->where('triggerable_type', $ticket->ticketable_type)
                ->where('triggerable_id', $ticket->ticketable_id)
                ->where('user_id', $ticket->assignee_id)
                ->max('created_at'),
        ];

        return collect($activityTimestamps)
            ->filter()
            ->map(fn ($timestamp): Carbon => Carbon::parse($timestamp))
            ->max();
    }
}
