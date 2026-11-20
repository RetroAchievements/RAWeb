<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Ticket;
use App\Models\Trigger;
use App\Platform\Enums\TriggerableType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Unlocks before a report show whether the reported logic can trigger. The
 * window for those unlocks opens at the logic version the ticket was filed
 * against, since older unlocks say nothing about the current logic. It never
 * reaches back more than 90 days before the report.
 */
class ResolveTicketUnlockWindowsAction
{
    private const MAX_DAYS = 90;

    /**
     * @param Collection<int, Ticket> $tickets achievement tickets
     * @return array<int, array{reportTrigger: ?Trigger, startedAt: Carbon, hasRecentLogicChange: bool}> keyed by ticket id
     */
    public function execute(Collection $tickets): array
    {
        $triggersByTicketableId = Trigger::withTrashed()
            ->ofType(TriggerableType::Achievement)
            ->whereIn('triggerable_id', $tickets->pluck('ticketable_id')->unique()->values())
            ->get(['id', 'triggerable_id', 'created_at', 'version'])
            ->groupBy('triggerable_id');

        $unlockWindowByTicketId = [];
        foreach ($tickets as $ticket) {
            $reportTrigger = $this->findReportTrigger($ticket, $triggersByTicketableId->get($ticket->ticketable_id, new Collection()));
            $maxStartedAt = Carbon::parse($ticket->created_at)->subDays(self::MAX_DAYS);
            $logicChangedAt = $reportTrigger?->created_at;
            $hasRecentLogicChange = $logicChangedAt?->greaterThan($maxStartedAt) ?? false;

            $unlockWindowByTicketId[$ticket->id] = [
                'reportTrigger' => $reportTrigger,
                'startedAt' => $hasRecentLogicChange ? Carbon::parse($logicChangedAt) : $maxStartedAt,
                'hasRecentLogicChange' => $hasRecentLogicChange,
            ];
        }

        return $unlockWindowByTicketId;
    }

    /**
     * Works out which logic version the ticket was filed against.
     *
     * @param Collection<int, Trigger> $achievementTriggers every logic version of the ticket's achievement
     */
    private function findReportTrigger(Ticket $ticket, Collection $achievementTriggers): ?Trigger
    {
        $ticketTrigger = $ticket->trigger_id ? $achievementTriggers->firstWhere('id', $ticket->trigger_id) : null;
        if ($ticketTrigger?->created_at) {
            return $ticketTrigger;
        }

        $reportedAt = Carbon::parse($ticket->created_at);

        return $achievementTriggers
            ->filter(fn (Trigger $trigger) => $trigger->created_at?->lessThanOrEqualTo($reportedAt))
            ->sortByDesc('created_at')
            ->first();
    }
}
