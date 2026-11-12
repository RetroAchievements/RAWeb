<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\Achievement;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\PlayerAchievement;
use App\Models\Ticket;
use App\Models\Trigger;
use App\Platform\Enums\TicketableType;
use App\Platform\Enums\TriggerableType;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TicketViewService
{
    private const MAX_OTHER_REPORTER_TICKETS = 100;

    public int $unlocksSinceReported = 0;
    public ?Carbon $logicChangedAfterReportAt = null;
    /** @var Ticket[] */
    public array $relatedTickets = [];
    /** @var Ticket[] */
    public array $reporterGameTickets = [];
    public ?PlayerAchievement $existingUnlock = null;
    public ?LeaderboardEntry $reporterLeaderboardEntry = null;
    public ?int $reportedTriggerVersion = null;
    public ?int $currentTriggerVersion = null;

    public function load(Ticket $ticket): void
    {
        $ticketable = $ticket->getTicketableModel();
        $isAchievement = $ticket->ticketable_type === TicketableType::Achievement->value;

        if ($ticket->reporter) {
            if ($isAchievement) {
                $this->existingUnlock = PlayerAchievement::where('user_id', $ticket->reporter->id)
                    ->where('achievement_id', $ticketable->id)
                    ->first();
            } else {
                $this->reporterLeaderboardEntry = LeaderboardEntry::where('leaderboard_id', $ticketable->id)
                    ->where('user_id', $ticket->reporter->id)
                    ->first();
            }
        }

        $this->relatedTickets = Ticket::query()
            ->with('reporter')
            ->where('ticketable_id', $ticket->ticketable_id)
            ->where('ticketable_type', $ticket->ticketable_type)
            ->where('id', '!=', $ticket->id)
            ->orderBy('state_sort_order')
            ->orderByDesc('created_at')
            ->get()
            ->all();

        $this->reporterGameTickets = $this->collectSameReporterTickets($ticket, $ticketable->getTicketableGame());

        $this->unlocksSinceReported = 0;
        $this->logicChangedAfterReportAt = null;
        if ($isAchievement && $ticket->state->isOpen()) {
            $logicChangedAt = Trigger::ofType(TriggerableType::Achievement)
                ->where('triggerable_id', $ticketable->id)
                ->whereNotNull('parent_id')
                ->where('created_at', '>=', Trigger::VERSIONING_CUTOFF)
                ->where('created_at', '>', $ticket->created_at)
                ->min('created_at');
            $this->logicChangedAfterReportAt = $logicChangedAt ? Carbon::parse($logicChangedAt) : null;

            // Manual unlocks and unlocks after a logic change don't tell us much about the reported logic.
            $this->unlocksSinceReported = PlayerAchievement::where('achievement_id', $ticketable->id)
                ->whereNull('unlocker_id')
                ->where('unlocked_effective_at', '>', $ticket->created_at)
                ->when($this->logicChangedAfterReportAt, fn ($query, Carbon $changedAt) => $query->where(
                    // A casual unlock inside the window still counts even if a hardcore unlock came later.
                    fn ($window) => $window
                        ->where(fn ($casual) => $casual->where('unlocked_at', '>', $ticket->created_at)->where('unlocked_at', '<', $changedAt))
                        ->orWhere(fn ($hardcore) => $hardcore->where('unlocked_hardcore_at', '>', $ticket->created_at)->where('unlocked_hardcore_at', '<', $changedAt))
                ))
                ->count();
        }

        $this->loadReportedTrigger($ticket, $isAchievement ? $ticketable : null);
    }

    /**
     * Gather the reporter's other tickets for this game and any of its subsets,
     * nearest in time to this ticket first, since those typically come from the
     * same play session.
     *
     * @return Ticket[]
     */
    private function collectSameReporterTickets(Ticket $ticket, Game $ticketableGame): array
    {
        if (!$ticket->reporter || $ticket->reporter->trashed()) {
            return [];
        }

        $scopeGameIds = $this->resolveGameFamilyIds($ticketableGame);

        return Ticket::query()
            ->with('ticketable')
            ->where('reporter_id', $ticket->reporter_id)
            ->where('id', '!=', $ticket->id)
            ->whereNot(fn ($exclusionQuery) => $exclusionQuery
                ->where('ticketable_type', $ticket->ticketable_type)
                ->where('ticketable_id', $ticket->ticketable_id)
            )
            ->whereHasMorph(
                'ticketable',
                [Achievement::class, Leaderboard::class],
                fn ($morphQuery) => $morphQuery->whereIn('game_id', $scopeGameIds),
            )
            ->get()
            ->sortBy(fn (Ticket $comparisonTicket) => abs($comparisonTicket->created_at->diffInSeconds($ticket->created_at)))
            ->values()
            ->all();
    }

    /**
     * @return Ticket[]
     */
    public function collectOtherReporterGameTickets(Ticket $ticket): array
    {
        $familyGameIds = $this->resolveGameFamilyIds($ticket->getTicketableModel()->getTicketableGame());
        $hasLiveReporter = $ticket->reporter && !$ticket->reporter->trashed();

        return Ticket::query()
            ->with(['ticketable', 'reporter'])
            ->where('id', '!=', $ticket->id)
            ->when($hasLiveReporter, fn ($gameTicketQuery) => $gameTicketQuery->where(fn ($reporterFilter) => $reporterFilter
                ->where('reporter_id', '!=', $ticket->reporter_id)
                ->orWhereNull('reporter_id')
            ))
            ->whereNot(fn ($ticketableFilter) => $ticketableFilter
                ->where('ticketable_type', $ticket->ticketable_type)
                ->where('ticketable_id', $ticket->ticketable_id)
            )
            ->whereHasMorph(
                'ticketable',
                [Achievement::class, Leaderboard::class],
                fn ($morphFilter) => $morphFilter->whereIn('game_id', $familyGameIds),
            )
            ->orderByDesc('created_at')
            ->limit(self::MAX_OTHER_REPORTER_TICKETS)
            ->get()
            ->all();
    }

    /**
     * @return Collection<int, int>
     */
    private function resolveGameFamilyIds(Game $sourceGame): Collection
    {
        $familyRootGameId = $sourceGame->parent_game_id ?? $sourceGame->id;

        return Game::query()
            ->where('id', $familyRootGameId)
            ->orWhere('parent_game_id', $familyRootGameId)
            ->pluck('id');
    }

    /**
     * Resolve the logic version that was current when the ticket was filed, plus the version that
     * is current now. This does not claim to be the version the reporter played. For the most
     * common ticket type there is no unlock, so that version is unknowable.
     */
    private function loadReportedTrigger(Ticket $ticket, ?Achievement $achievement): void
    {
        $this->reportedTriggerVersion = null;
        $this->currentTriggerVersion = null;

        if (!$achievement || !$ticket->trigger_id) {
            return;
        }

        $this->reportedTriggerVersion = $ticket->trigger?->version;
        if ($this->reportedTriggerVersion === null) {
            return;
        }

        $this->currentTriggerVersion = $achievement->trigger_id === $ticket->trigger_id
            ? $this->reportedTriggerVersion
            : $achievement->currentTrigger?->version;
    }
}
