<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\TicketState;
use App\Models\Achievement;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\Ticket;
use App\Support\Alerts\TicketNegligenceAlert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * @see https://docs.retroachievements.org/guidelines/developers/code-of-conduct.html#ticket-negligence
 * Uses slightly looser thresholds (based on guidance from some Developer Compliance members).
 *
 * A dev gets flagged when either of these is true:
 *   - They have one ticket unaddressed for 90+ days.
 *   - They have 8 or more tickets unaddressed for 30+ days each.
 */
class DetectNegligentDevelopersAction
{
    private const UNADDRESSED_AFTER_DAYS = 14;
    private const STALE_AFTER_DAYS = 30;
    private const STALE_TICKET_LIMIT = 8;
    private const NEGLECTED_AFTER_DAYS = 90;
    private const DEDUPE_TTL_DAYS = 90;
    private const MAX_ENTRIES_PER_ALERT = 8;

    public function execute(): int
    {
        if (!TicketNegligenceAlert::webhookUrl()) {
            return 0;
        }

        $neglectedBefore = Carbon::now()->subDays(self::NEGLECTED_AFTER_DAYS);

        $alertEntries = $this->getUnaddressedStaleTickets()
            ->groupBy('assignee_id')
            ->filter(fn (Collection $tickets) => $tickets->count() >= self::STALE_TICKET_LIMIT
                || $tickets->contains(fn (Ticket $ticket) => $ticket->created_at->lte($neglectedBefore))
            )
            ->filter(fn (Collection $tickets, int $developerId) => Cache::add("alerts:ticket-negligence:{$developerId}", true, Carbon::now()->addDays(self::DEDUPE_TTL_DAYS)))
            ->map(fn (Collection $tickets) => [
                'displayName' => $tickets->first()->assignee->display_name,
                'staleTicketCount' => $tickets->count(),
            ])
            ->sortBy('displayName', SORT_STRING | SORT_FLAG_CASE)
            ->values()
            ->all();

        // chunks so we don't hit Discord's char limit
        foreach (array_chunk($alertEntries, self::MAX_ENTRIES_PER_ALERT) as $entries) {
            (new TicketNegligenceAlert($entries))->send();
        }

        return count($alertEntries);
    }

    /**
     * @return Collection<int, Ticket>
     */
    private function getUnaddressedStaleTickets(): Collection
    {
        return Ticket::query()
            ->where('state', TicketState::Open)
            ->where('created_at', '<=', Carbon::now()->subDays(self::STALE_AFTER_DAYS))
            ->whereHasMorph('ticketable', [Achievement::class, Leaderboard::class])
            ->whereHas('assignee', fn (Builder $query) => $query
                ->whereNull('deleted_at')
                ->whereNull('banned_at')
                ->whereHas('roles', fn (Builder $query) => $query->whereIn('name', [Role::DEVELOPER, Role::DEVELOPER_JUNIOR]))
                ->whereDoesntHave('roles', fn (Builder $query) => $query->whereIn('name', [Role::ROOT, Role::TEAM_ACCOUNT]))
            )
            ->whereDoesntHave('comments', fn (Builder $query) => $query
                ->whereColumn('comments.user_id', 'tickets.assignee_id')
                ->where('created_at', '>=', Carbon::now()->subDays(self::UNADDRESSED_AFTER_DAYS))
            )
            ->with([
                'assignee',
                'ticketable' => fn (MorphTo $morphTo) => $morphTo->morphWith([Achievement::class => ['activeMaintainer']]),
            ])
            ->get()
            ->reject(fn (Ticket $ticket) => $ticket->isClaimed());
    }
}
