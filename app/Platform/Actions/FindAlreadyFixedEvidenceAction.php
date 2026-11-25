<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\TicketResolution;
use App\Models\Achievement;
use App\Models\PlayerSession;
use App\Models\Ticket;
use App\Models\Trigger;
use App\Platform\Data\TicketAlreadyFixedEvidenceData;
use App\Platform\Enums\TriggerableType;
use Carbon\Carbon;

/**
 * Returns evidence that the reporter probably still had the old logic loaded.
 * All three of these must be true:
 * - The logic changed in the 7 days before the ticket was created.
 * - The reporter's newest session on the game before creation started before that change.
 * - The logic has not changed since the ticket was created. A later change usually means the report found a real defect.
 */
class FindAlreadyFixedEvidenceAction
{
    private const LOGIC_CHANGE_WINDOW_DAYS = 7;

    public function execute(Ticket $ticket): ?TicketAlreadyFixedEvidenceData
    {
        $achievement = $ticket->ticketable;
        if (!$achievement instanceof Achievement || !$ticket->reporter_id) {
            return null;
        }

        if ($ticket->created_at->lt(Trigger::VERSIONING_CUTOFF)) {
            return null;
        }

        $latestLogicChange = Trigger::ofType(TriggerableType::Achievement)
            ->where('triggerable_id', $achievement->id)
            ->whereNotNull('parent_id')
            ->where('created_at', '>=', Trigger::VERSIONING_CUTOFF)
            ->where('created_at', '>=', $ticket->created_at->copy()->subDays(self::LOGIC_CHANGE_WINDOW_DAYS))
            ->max('created_at');
        if (!$latestLogicChange) {
            return null;
        }

        $logicChangedAt = Carbon::parse($latestLogicChange);
        if ($logicChangedAt->greaterThan($ticket->created_at)) {
            return null;
        }

        $latestSessionStart = PlayerSession::query()
            ->where('user_id', $ticket->reporter_id)
            ->whereIn('game_id', $achievement->getRelatedGameIds()) // subsets from base game hashes
            ->where('created_at', '<=', $ticket->created_at)
            ->max('created_at');
        if (!$latestSessionStart) {
            return null;
        }

        $reporterSessionStartedAt = Carbon::parse($latestSessionStart);
        if (!$reporterSessionStartedAt->lessThan($logicChangedAt)) {
            return null;
        }

        return new TicketAlreadyFixedEvidenceData(
            reporterSessionStartedAt: $reporterSessionStartedAt,
            logicChangedAt: $logicChangedAt,
            coveringTicketId: $this->findCoveringTicketId($ticket, $logicChangedAt),
        );
    }

    private function findCoveringTicketId(Ticket $ticket, Carbon $logicChangedAt): ?int
    {
        return Ticket::query()
            ->where('ticketable_type', $ticket->ticketable_type)
            ->where('ticketable_id', $ticket->ticketable_id)
            ->where('id', '!=', $ticket->id)
            ->where('created_at', '<', $logicChangedAt)
            ->where('resolution', TicketResolution::Fixed)
            ->where('resolved_at', '>=', $logicChangedAt)
            ->orderByDesc('created_at')
            ->value('id');
    }
}
