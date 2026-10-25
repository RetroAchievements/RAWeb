<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Data\CommentData;
use App\Community\Enums\TicketAction;
use App\Data\UserData;
use App\Data\UserPermissionsData;
use App\Models\Achievement;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Platform\Data\AchievementData;
use App\Platform\Data\LeaderboardData;
use App\Platform\Data\LeaderboardEntryData;
use App\Platform\Data\TicketListEntryData;
use App\Platform\Data\TicketRelatedEntryData;
use App\Platform\Data\TicketReporterUnlockData;
use App\Platform\Data\TicketShowPagePropsData;
use App\Platform\Services\TicketViewService;
use Spatie\LaravelData\Lazy;

class BuildTicketShowPagePropsAction
{
    public function __construct(
        private readonly BuildPlayerGameActivityDataAction $buildPlayerActivityData,
    ) {
    }

    public function execute(Ticket $ticket, User $user): TicketShowPagePropsData
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

        // NOTE: Ticket comments from banned users are always visible, intentionally.
        // Loading the relation here lets the policy reuse it for the Request rule.
        $ticket->load(['discussionComments.user' => fn ($query) => $query->withTrashed()]);
        $loadedComments = $ticket->discussionComments;

        $canViewReporterHistory = $user->canAny(['manage', 'viewHistory'], Ticket::class);
        $hasReporterHistoryAccess = $canViewReporterHistory && $ticket->reporter !== null;

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

            isViewerReporter: $user->is($ticket->reporter),

            can: UserPermissionsData::fromUser($user, triggerable: $ticketable, ticket: $ticket)->include(
                'createTicketComments',
                'manuallyAwardAchievements',
                'viewAchievementLogic'
            ),

            relatedTickets: array_map(
                fn (Ticket $relatedTicket): TicketRelatedEntryData => TicketRelatedEntryData::fromTicket($relatedTicket),
                $ticketViewService->relatedTickets,
            ),

            reporterUnlock: $this->buildReporterUnlock($ticketViewService, $canViewReporterHistory),

            unlocksSinceReported: $ticketable instanceof Achievement && $ticket->state->isOpen()
                ? $ticketViewService->unlocksSinceReported
                : null,

            reportedTriggerVersion: $ticketViewService->reportedTriggerVersion,
            currentTriggerVersion: $ticketViewService->currentTriggerVersion,
            reporterLeaderboardEntry: $reporterLeaderboardEntry,
            leaderboardEntryCount: $leaderboardEntryCount,

            reportBody: $ticket->body !== '' ? $ticket->body : null,

            comments: array_map(
                fn (TicketComment $comment): CommentData => CommentData::fromComment($comment),
                $loadedComments->all(),
            ),

            changelog: (new BuildTicketChangelogAction())->execute($ticket),
            allowedTicketActions: $this->collectAllowedTicketActions($ticket, $user),
            hasNonReporterComment: $ticket->has_non_reporter_comment,
            hasReporterRepliedLast: $ticket->has_reporter_replied_last,

            hasReporterHistoryAccess: $hasReporterHistoryAccess,
            reporterActivity: $hasReporterHistoryAccess ? $this->deferReporterActivity($ticket, $ticketable) : null,
        );

        return $propsData;
    }

    private function deferReporterActivity(Ticket $ticket, Achievement|Leaderboard $ticketable): Lazy
    {
        return Lazy::inertiaDeferred(fn () => $this->buildPlayerActivityData->execute(
            $ticket->reporter,
            $ticketable->getTicketableGame(),
            ticket: $ticket,
        ));
    }

    /**
     * @return TicketAction[]
     */
    private function collectAllowedTicketActions(Ticket $ticket, User $user): array
    {
        $allowedActions = array_values(array_filter(
            TicketAction::cases(),
            fn (TicketAction $ticketAction): bool => $user->can('updateState', [$ticket, $ticketAction]),
        ));

        // Reporters shouldn't be able to ad-hoc reopen tickets.
        if (!$user->hasAnyRole([Role::DEVELOPER, Role::MODERATOR, Role::ADMINISTRATOR])) {
            return array_values(array_filter(
                $allowedActions,
                fn (TicketAction $ticketAction): bool => $ticketAction !== TicketAction::Reopen,
            ));
        }

        return $allowedActions;
    }

    /**
     * Only roles allowed to see player history receive it in page props.
     */
    private function buildReporterUnlock(TicketViewService $ticketViewService, bool $canViewReporterHistory): ?TicketReporterUnlockData
    {
        $unlock = $ticketViewService->existingUnlock;
        if (!$unlock || !$canViewReporterHistory) {
            return null;
        }

        return new TicketReporterUnlockData(
            unlockedAt: $unlock->unlocked_effective_at,
            isHardcore: $unlock->unlocked_hardcore_at !== null,
            unlocker: $unlock->unlocker ? UserData::fromUser($unlock->unlocker) : null,
        );
    }
}
