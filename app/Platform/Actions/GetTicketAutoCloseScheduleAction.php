<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\TicketState;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Platform\Data\TicketAutoCloseScheduleData;
use Illuminate\Support\Carbon;

class GetTicketAutoCloseScheduleAction
{
    private const REMINDER_DELAY_DAYS = 7;
    private const CLOSING_DELAY_AFTER_REMINDER_DAYS = 7;

    /**
     * Returns data about when the CloseUnansweredTickets scheduled job should remind
     * the ticket reporter or close the ticket outright. If this returns null, it means
     * that job should leave the given `$ticket` alone.
     */
    public function execute(Ticket $ticket): ?TicketAutoCloseScheduleData
    {
        if ($ticket->state !== TicketState::Request) {
            return null;
        }

        // A reporter who can't comment right now can't answer either, so let the dev deal with the ticket instead.
        // This will happen if the reporter gets muted, banned, etc.
        if (!$ticket->reporter?->can('create', [TicketComment::class, $ticket])) {
            return null;
        }

        $latestCommentAt = $ticket->comments()->max('created_at');
        $latestReminderAt = $ticket->auditLog()->where('event', Ticket::REPORTER_REMINDED_EVENT)->max('created_at');

        // Any comment restarts the wait period.
        $hasReminder = $latestReminderAt && (!$latestCommentAt || $latestReminderAt >= $latestCommentAt);

        $remindAt = $hasReminder
            ? Carbon::parse($latestReminderAt)
            : Carbon::parse($latestCommentAt ?? $ticket->updated_at)->addDays(self::REMINDER_DELAY_DAYS);

        // A reminder that is already overdue goes out on the next run.
        $closingStartAt = $hasReminder ? $remindAt->copy() : $remindAt->copy()->max(now());
        $closeAt = $closingStartAt->addDays(self::CLOSING_DELAY_AFTER_REMINDER_DAYS);

        return new TicketAutoCloseScheduleData(isReminded: $hasReminder, remindAt: $remindAt, closeAt: $closeAt);
    }
}
