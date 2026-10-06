<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Data\UserData;
use App\Data\UserPermissionsData;
use App\Models\Achievement;
use App\Models\Leaderboard;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Data\AchievementData;
use App\Platform\Data\LeaderboardData;
use App\Platform\Data\LeaderboardEntryData;
use App\Platform\Data\TicketListEntryData;
use App\Platform\Data\TicketRelatedEntryData;
use App\Platform\Data\TicketReporterUnlockData;
use App\Platform\Data\TicketShowPagePropsData;
use App\Platform\Services\TicketViewService;

class BuildTicketShowPagePropsAction
{
    public function execute(Ticket $ticket, ?User $user): TicketShowPagePropsData
    {
        $ticketViewService = new TicketViewService();
        $ticketViewService->load($ticket);

        $ticketable = $ticket->getTicketableModel();

        $reporterLeaderboardEntry = null;
        $leaderboardEntryCount = null;

        $reporterEntry = $ticketViewService->reporterLeaderboardEntry;
        if ($ticketable instanceof Leaderboard && $reporterEntry) {
            $reporterLeaderboardEntry = LeaderboardEntryData::fromLeaderboardEntry(
                $reporterEntry,
                $ticketable->format,
                rank: $ticketable->getRank($reporterEntry->score),
            )->include('formattedScore', 'rank');

            $leaderboardEntryCount = $ticketable->entries()->count();
        }

        $propsData = new TicketShowPagePropsData(
            ticket: TicketListEntryData::fromTicket($ticket),

            achievement: $ticketable instanceof Achievement
                ? AchievementData::fromAchievement($ticketable)->include('points', 'developer.isGone')
                : null,

            leaderboard: $ticketable instanceof Leaderboard
                ? LeaderboardData::fromLeaderboard($ticketable)->include('format', 'rankAsc', 'developer.isGone')
                : null,

            ticketableDescription: $ticketable->description,

            // The ticket author is whoever maintained the achievement when the ticket was filed.
            hasMaintainer: $ticketable instanceof Achievement && !$ticket->author->is($ticketable->developer),

            can: UserPermissionsData::fromUser($user, triggerable: $ticketable)->include('viewAchievementLogic'),

            relatedTickets: array_map(
                fn (Ticket $relatedTicket): TicketRelatedEntryData => TicketRelatedEntryData::fromTicket($relatedTicket),
                $ticketViewService->relatedTickets,
            ),

            reporterUnlock: $this->buildReporterUnlock($ticketViewService, $user),

            unlocksSinceReported: $ticketable instanceof Achievement && $ticket->state->isOpen()
                ? $ticketViewService->unlocksSinceReported
                : null,

            reportedTriggerVersion: $ticketViewService->reportedTriggerVersion,
            currentTriggerVersion: $ticketViewService->currentTriggerVersion,
            reporterLeaderboardEntry: $reporterLeaderboardEntry,
            leaderboardEntryCount: $leaderboardEntryCount,
        );

        return $propsData;
    }

    /**
     * Only roles allowed to see player history receive it in page props.
     */
    private function buildReporterUnlock(TicketViewService $ticketViewService, ?User $user): ?TicketReporterUnlockData
    {
        $unlock = $ticketViewService->existingUnlock;
        if (!$unlock || !$user?->canAny(['manage', 'viewHistory'], Ticket::class)) {
            return null;
        }

        return new TicketReporterUnlockData(
            unlockedAt: $unlock->unlocked_effective_at,
            isHardcore: $unlock->unlocked_hardcore_at !== null,
            unlocker: $unlock->unlocker ? UserData::fromUser($unlock->unlocker) : null,
        );
    }
}
