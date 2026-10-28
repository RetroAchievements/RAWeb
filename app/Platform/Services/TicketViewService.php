<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\Achievement;
use App\Models\LeaderboardEntry;
use App\Models\PlayerAchievement;
use App\Models\Ticket;
use App\Platform\Enums\TicketableType;

class TicketViewService
{
    public int $unlocksSinceReported = 0;
    /** @var Ticket[] */
    public array $relatedTickets = [];
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
            ->where('ticketable_id', $ticket->ticketable_id)
            ->where('ticketable_type', $ticket->ticketable_type)
            ->where('id', '!=', $ticket->id)
            ->orderBy('state_sort_order')
            ->orderByDesc('created_at')
            ->get()
            ->all();

        $this->unlocksSinceReported = 0;
        if ($isAchievement && $ticket->state->isOpen()) {
            $this->unlocksSinceReported = PlayerAchievement::where('achievement_id', $ticketable->id)
                ->where(function ($query) use ($ticket) {
                    $query->where('unlocked_at', '>', $ticket->created_at)
                        ->orWhere('unlocked_hardcore_at', '>', $ticket->created_at);
                })->count();
        }

        $this->loadReportedTrigger($ticket, $isAchievement ? $ticketable : null);
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
