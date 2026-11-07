<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\TicketSignalVerdict;
use App\Community\Enums\TicketState;
use App\Models\Ticket;
use App\Models\TicketVerdictAccuracyResult;
use App\Models\Trigger;
use App\Platform\Enums\TriggerableType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

/**
 * Scores each signal verdict against what actually happened to the tickets
 * that finished inside the window. The verdict scored for a ticket is
 * the one it held when it was marked closed or resolved.
 */
class MeasureTicketVerdictAccuracyAction
{
    private const WINDOW_DAYS = 365;

    /**
     * @return Collection<int, TicketVerdictAccuracyResult> one stored result per verdict
     */
    public function execute(): Collection
    {
        $windowEndedAt = Carbon::now();
        $windowStartedAt = $windowEndedAt->copy()->subDays(self::WINDOW_DAYS);

        $finishedTicketQuery = Ticket::query()
            ->whereIn('state', [TicketState::Resolved, TicketState::Closed])
            ->whereNotNull('resolved_signal_verdict')
            ->whereBetween('resolved_at', [$windowStartedAt, $windowEndedAt]);

        $finishedCountByVerdict = $this->countTicketsByVerdict($finishedTicketQuery->clone());
        $changedCountByVerdict = $this->countTicketsByVerdict($finishedTicketQuery->clone()->whereExists(
            Trigger::query()
                ->ofType(TriggerableType::Achievement)
                ->whereColumn('triggers.triggerable_id', 'tickets.ticketable_id')
                ->whereNotNull('triggers.parent_id')
                ->whereColumn('triggers.created_at', '>', 'tickets.created_at')
                ->whereColumn('triggers.created_at', '<=', 'tickets.resolved_at'),
        ));

        return DB::transaction(function () use ($finishedCountByVerdict, $changedCountByVerdict, $windowStartedAt, $windowEndedAt) {
            $createdResults = [];

            foreach (TicketSignalVerdict::cases() as $verdict) {
                $finishedCount = (int) ($finishedCountByVerdict[$verdict->value] ?? 0);
                $changedCount = (int) ($changedCountByVerdict[$verdict->value] ?? 0);

                $createdResults[] = TicketVerdictAccuracyResult::create([
                    'verdict' => $verdict,
                    'ticket_count' => $finishedCount,
                    'correct_count' => match ($verdict) {
                        TicketSignalVerdict::ProbablyDefect => $changedCount,
                        TicketSignalVerdict::ProbablyNotDefect => $finishedCount - $changedCount,
                    },
                    'window_started_at' => $windowStartedAt,
                    'window_ended_at' => $windowEndedAt,
                ]);
            }

            return new Collection($createdResults);
        });
    }

    /**
     * @param Builder<Ticket> $tickets
     * @return SupportCollection<string, int> keyed by verdict value
     */
    private function countTicketsByVerdict(Builder $tickets): SupportCollection
    {
        return $tickets
            ->groupBy('resolved_signal_verdict')
            ->selectRaw('resolved_signal_verdict, COUNT(*) as ticket_count')
            ->pluck('ticket_count', 'resolved_signal_verdict');
    }
}
