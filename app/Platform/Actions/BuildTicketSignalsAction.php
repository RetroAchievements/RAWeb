<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\TicketSignal;
use App\Community\Enums\TicketSignalVerdict;
use App\Community\Enums\TicketType;
use App\Models\Achievement;
use App\Models\PlayerAchievement;
use App\Models\PlayerSession;
use App\Models\Ticket;
use App\Models\Trigger;
use App\Platform\Data\TicketSignalsData;
use App\Platform\Enums\TicketableType;
use App\Platform\Enums\TriggerableType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * This action judges whether an open "did not trigger" ticket is most likely
 * a real bug, most likely not a bug, or neither. Every rule below is derived
 * from how 14,636 finished "did not trigger" tickets actually turned out.
 * Those tickets were filed between 2025-09-19 and 2026-09-19. In that set,
 * 62.5% were closed without a fix, while 37.5% were resolved as bugs with an
 * accompanying logic change. A signal only earns a rule when it pushes a
 * ticket far away from that 62.5% average.
 *
 * Unlocks in the window before the report are the strongest signal. The
 * window opens at the last logic change, since older unlocks say nothing
 * about the current logic. The window never reaches back more than 90 days.
 * The closed share rises steadily as the count grows:
 *
 *   0 unlocks         22% closed, so 78% were real bugs
 *   1 to 9 unlocks    44% to 59% closed, too close to the average to call
 *   10 to 49 unlocks  70% to 78% closed
 *   50 to 99 unlocks  81% to 83% closed
 *   100+ unlocks      85% to 92% closed
 *
 * The threshold is 50 rather than 100. A threshold of 50 covers 4,574
 * tickets at about 87% closed. A threshold of 100 covers only 3,135 tickets
 * at about 89% closed, so we're asymptoting by that point.
 *
 * Zero unlocks only means something when enough other people played the
 * game. With fewer than 5 other players in the window, only ~55% of
 * zero-unlock tickets got a logic change (effectively, a coin flip),
 * against ~79% with 5 or more players.
 *
 * "The reporter earned it in play after the report" was barely better than
 * the average on its own, so it never decides a verdict by itself. It becomes
 * useful when both the reporter's unlock and the ticket record a logic
 * version. Unlocks only started recording a logic version in 2026.
 * - The same version shows the reported logic can trigger, but not that it
 *   always does. An achievement can work most of the time and still fail in
 *   rare cases, therefore this does not decide a verdict by itself either.
 * - A newer version means a fix likely shipped while the ticket stayed open.
 *   In other words, the ticket is likely a real bug that is already fixed.
 * - A same or unknown version cancels "no unlocks". The reporter's own unlock
 *   shows the logic can trigger, and with an unknown version the two together
 *   were a real bug only ~60% of the time.
 *
 * The backtest counted only reporter unlocks from before each ticket was
 * finished. About half of all reporter unlocks came after the ticket was
 * finished, often because the fix made the unlock possible. Counting those
 * unlocks would make the logic version rules look far better than they
 * actually are.
 */
class BuildTicketSignalsAction
{
    private const LOOKBACK_DAYS = 90;
    private const MANY_UNLOCKS_FLOOR = 50;
    private const MIN_OTHER_PLAYERS = 5;

    /**
     * @param Collection<int, Ticket> $tickets
     * @return array<int, TicketSignalsData|null> keyed by ticket id, null when the ticket has no verdict
     */
    public function execute(Collection $tickets): array
    {
        $signalsForTickets = array_fill_keys($tickets->modelKeys(), null);

        $signalEligibleTickets = $tickets->filter(fn (Ticket $ticket) => $this->isSignalEligible($ticket))->values();
        if ($signalEligibleTickets->isEmpty()) {
            return $signalsForTickets;
        }

        $signalEligibleTickets->loadMissing('ticketable');

        $triggersByTicketId = $this->findReportTriggersByTicket($signalEligibleTickets);
        $windowStartByTicketId = $this->computeWindowStarts($signalEligibleTickets, $triggersByTicketId);
        $unlockCountByTicketId = $this->countWindowUnlocks($signalEligibleTickets, $windowStartByTicketId);
        $laterReporterUnlockByTicketId = $this->findLaterReporterUnlocks($signalEligibleTickets);
        $hasEnoughPlayersByTicketId = $this->findTicketsWithEnoughOtherPlayers(
            $signalEligibleTickets->filter(fn (Ticket $ticket) => ($unlockCountByTicketId[$ticket->id] ?? 0) === 0),
            $windowStartByTicketId,
        );

        foreach ($signalEligibleTickets as $ticket) {
            $windowUnlockCount = $unlockCountByTicketId[$ticket->id] ?? 0;
            $laterReporterUnlock = $laterReporterUnlockByTicketId[$ticket->id] ?? null;
            $unlockSignal = $laterReporterUnlock
                ? $this->classifyUnlockAgainstReport($laterReporterUnlock, $ticket, $triggersByTicketId[$ticket->id])
                : null;
            $ticketable = $ticket->ticketable;
            $isPromotedAchievement = $ticketable instanceof Achievement && $ticketable->is_promoted;

            $matchedSignals = [
                TicketSignal::ManyRecentUnlocks->value => $windowUnlockCount >= self::MANY_UNLOCKS_FLOOR,
                TicketSignal::EarnedWithSameLogic->value => $unlockSignal === TicketSignal::EarnedWithSameLogic,
                TicketSignal::EarnedAfterReport->value => $unlockSignal === TicketSignal::EarnedAfterReport,
                TicketSignal::EarnedAfterLogicChange->value => $unlockSignal === TicketSignal::EarnedAfterLogicChange,
                TicketSignal::NoRecentUnlocks->value => $windowUnlockCount === 0
                    && $isPromotedAchievement
                    && isset($hasEnoughPlayersByTicketId[$ticket->id]),
            ];

            $signalVerdict = $this->decideVerdict($matchedSignals);
            if ($signalVerdict === null) {
                continue;
            }

            $signalsForTickets[$ticket->id] = new TicketSignalsData(
                verdict: $signalVerdict,
                kinds: array_values(array_filter(
                    TicketSignal::cases(),
                    fn (TicketSignal $ticketSignal) => $matchedSignals[$ticketSignal->value],
                )),
                unlockCount: $windowUnlockCount,
                windowStartedAt: $windowStartByTicketId[$ticket->id],
                hasRecentLogicChange: $windowStartByTicketId[$ticket->id]->greaterThan(
                    Carbon::parse($ticket->created_at)->subDays(self::LOOKBACK_DAYS),
                ),
                reporterEarnedAt: $laterReporterUnlock?->unlocked_effective_at,
            );
        }

        return $signalsForTickets;
    }

    /**
     * The first rule that matches wins.
     *
     * Whenever a defect signal and a not-defect signal both apply, the defect
     * signal takes precedence.
     *
     * A later reporter unlock on the same or an unknown logic version never
     * decides a verdict, and it cancels "no recent unlocks".
     *
     * @param array<string, bool> $matchedSignals keyed by a TicketSignal value
     */
    private function decideVerdict(array $matchedSignals): ?TicketSignalVerdict
    {
        if ($matchedSignals[TicketSignal::EarnedAfterLogicChange->value]) {
            return TicketSignalVerdict::ProbablyDefect;
        }

        if ($matchedSignals[TicketSignal::NoRecentUnlocks->value]) {
            return $matchedSignals[TicketSignal::EarnedAfterReport->value] || $matchedSignals[TicketSignal::EarnedWithSameLogic->value]
                ? null
                : TicketSignalVerdict::ProbablyDefect;
        }

        if ($matchedSignals[TicketSignal::ManyRecentUnlocks->value]) {
            return TicketSignalVerdict::ProbablyNotDefect;
        }

        return null;
    }

    /**
     * Unlocks only began recording their logic version in 2026, so older
     * unlocks fall back to the plain "earned after report" signal.
     */
    private function classifyUnlockAgainstReport(PlayerAchievement $unlock, Ticket $ticket, ?Trigger $reportTrigger): TicketSignal
    {
        $unlockVersion = $unlock->unlocked_at->greaterThan($ticket->created_at) ? $unlock->trigger_version : null;
        $reportVersion = $reportTrigger?->version;
        if ($unlockVersion === null || $reportVersion === null || $unlockVersion < $reportVersion) {
            return TicketSignal::EarnedAfterReport;
        }

        return $unlockVersion === $reportVersion
            ? TicketSignal::EarnedWithSameLogic
            : TicketSignal::EarnedAfterLogicChange;
    }

    private function isSignalEligible(Ticket $ticket): bool
    {
        return
            $ticket->ticketable_type === TicketableType::Achievement->value
            && $ticket->type === TicketType::DidNotTrigger
            && !$ticket->state->isResolved();
    }

    /**
     * Works out which logic version each ticket was filed against.
     *
     * @param Collection<int, Ticket> $tickets
     * @return array<int, Trigger|null> keyed by ticket id
     */
    private function findReportTriggersByTicket(Collection $tickets): array
    {
        $triggersByTicketableId = Trigger::withTrashed()
            ->ofType(TriggerableType::Achievement)
            ->whereIn('triggerable_id', $tickets->pluck('ticketable_id')->unique()->values())
            ->get(['id', 'triggerable_id', 'created_at', 'version'])
            ->groupBy('triggerable_id');

        $triggersByTicketId = [];
        foreach ($tickets as $ticket) {
            $triggersByTicketId[$ticket->id] = $this->findReportTriggerForTicket(
                $ticket,
                $triggersByTicketableId->get($ticket->ticketable_id, new Collection()),
            );
        }

        return $triggersByTicketId;
    }

    /**
     * @param Collection<int, Trigger> $triggers
     */
    private function findReportTriggerForTicket(Ticket $ticket, Collection $triggers): ?Trigger
    {
        $ticketTrigger = $ticket->trigger_id ? $triggers->firstWhere('id', $ticket->trigger_id) : null;
        if ($ticketTrigger?->created_at) {
            return $ticketTrigger;
        }

        $reportedAt = Carbon::parse($ticket->created_at);

        return $triggers
            ->filter(fn (Trigger $trigger) => $trigger->created_at?->lessThanOrEqualTo($reportedAt))
            ->sortByDesc('created_at')
            ->first();
    }

    /**
     * @param Collection<int, Ticket> $tickets
     * @param array<int, Trigger|null> $triggersByTicketId
     * @return array<int, Carbon> keyed by ticket id
     */
    private function computeWindowStarts(Collection $tickets, array $triggersByTicketId): array
    {
        $windowStartByTicketId = [];
        foreach ($tickets as $ticket) {
            $lookbackStart = Carbon::parse($ticket->created_at)->subDays(self::LOOKBACK_DAYS);
            $triggerMoment = $triggersByTicketId[$ticket->id]?->created_at;

            $windowStartByTicketId[$ticket->id] = $triggerMoment?->greaterThan($lookbackStart)
                ? Carbon::parse($triggerMoment)
                : $lookbackStart;
        }

        return $windowStartByTicketId;
    }

    /**
     * @param Collection<int, Ticket> $tickets
     * @param array<int, Carbon> $windowStartByTicketId
     * @return array<int, int> keyed by ticket id
     */
    private function countWindowUnlocks(Collection $tickets, array $windowStartByTicketId): array
    {
        $perTicketQueries = $tickets->map(fn (Ticket $ticket) => PlayerAchievement::query()
            ->selectRaw('? as ticket_id, COUNT(*) as unlock_count', [$ticket->id])
            ->where('achievement_id', $ticket->ticketable_id)
            ->whereBetween('unlocked_effective_at', [
                $windowStartByTicketId[$ticket->id],
                Carbon::parse($ticket->created_at),
            ])
            ->toBase()
        );

        $unionQuery = $perTicketQueries->first();
        $perTicketQueries->skip(1)->each(
            // force range scans on the (achievement_id, unlocked_effective_at) index
            fn ($perTicketQuery) => $unionQuery->unionAll($perTicketQuery),
        );

        $unlockCountByTicketId = [];
        foreach ($unionQuery->get() as $countRow) {
            $unlockCountByTicketId[(int) $countRow->ticket_id] = (int) $countRow->unlock_count;
        }

        return $unlockCountByTicketId;
    }

    /**
     * @param Collection<int, Ticket> $tickets
     * @param array<int, Carbon> $windowStartByTicketId
     * @return array<int, true> keyed by ticket id, only for tickets whose game had enough other players in the window
     */
    private function findTicketsWithEnoughOtherPlayers(Collection $tickets, array $windowStartByTicketId): array
    {
        if ($tickets->isEmpty()) {
            return [];
        }

        $perTicketQueries = $tickets->map(function (Ticket $ticket) use ($windowStartByTicketId) {
            $otherPlayers = PlayerSession::query()
                ->select('user_id')
                ->distinct()
                ->where('game_id', $ticket->ticketable->game_id)
                ->when($ticket->reporter_id, fn ($query, int $reporterId) => $query->where('user_id', '!=', $reporterId))
                ->whereBetween('rich_presence_updated_at', [
                    $windowStartByTicketId[$ticket->id],
                    Carbon::parse($ticket->created_at),
                ])
                ->limit(self::MIN_OTHER_PLAYERS) // stop scanning popular games once they have enough players
                ->toBase();

            return DB::query()
                ->fromSub($otherPlayers, 'other_players')
                ->selectRaw('? as ticket_id, COUNT(*) as player_count', [$ticket->id]);
        });

        $unionQuery = $perTicketQueries->first();
        $perTicketQueries->skip(1)->each(fn ($perTicketQuery) => $unionQuery->unionAll($perTicketQuery));

        $hasEnoughPlayersByTicketId = [];
        foreach ($unionQuery->get() as $countRow) {
            if ((int) $countRow->player_count >= self::MIN_OTHER_PLAYERS) {
                $hasEnoughPlayersByTicketId[(int) $countRow->ticket_id] = true;
            }
        }

        return $hasEnoughPlayersByTicketId;
    }

    /**
     * @param Collection<int, Ticket> $tickets
     * @return array<int, PlayerAchievement> the reporter's own (not manual) unlock keyed by ticket id, only when it happened after the ticket was created
     */
    private function findLaterReporterUnlocks(Collection $tickets): array
    {
        $ticketsWithReporter = $tickets->filter(fn (Ticket $ticket) => $ticket->reporter_id !== null);
        if ($ticketsWithReporter->isEmpty()) {
            return [];
        }

        $unlocksByUserAndAchievement = PlayerAchievement::query()
            ->leftJoin('triggers', 'triggers.id', '=', 'player_achievements.trigger_id')
            ->whereNull('unlocker_id')
            ->whereIn('player_achievements.user_id', $ticketsWithReporter->pluck('reporter_id')->unique()->values())
            ->whereIn('achievement_id', $ticketsWithReporter->pluck('ticketable_id')->unique()->values())
            ->whereNotNull('unlocked_effective_at')
            ->get([
                'player_achievements.user_id',
                'achievement_id',
                'unlocked_at',
                'unlocked_effective_at',
                'trigger_id',
                'triggers.version as trigger_version',
            ])
            ->keyBy(fn (PlayerAchievement $unlock) => "{$unlock->user_id}:{$unlock->achievement_id}");

        $laterReporterUnlockByTicketId = [];
        foreach ($ticketsWithReporter as $ticket) {
            $reporterUnlock = $unlocksByUserAndAchievement->get("{$ticket->reporter_id}:{$ticket->ticketable_id}");
            if ($reporterUnlock?->unlocked_effective_at->greaterThan(Carbon::parse($ticket->created_at))) {
                $laterReporterUnlockByTicketId[$ticket->id] = $reporterUnlock;
            }
        }

        return $laterReporterUnlockByTicketId;
    }
}
