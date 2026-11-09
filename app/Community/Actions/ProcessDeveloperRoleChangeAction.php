<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Enums\ClaimStatus;
use App\Community\Enums\CommentableType;
use App\Community\Enums\TicketState;
use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\AchievementMaintainer;
use App\Models\AchievementSetClaim;
use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Actions\ReleaseTicketClaimAction;
use App\Platform\Enums\TicketableType;
use App\Platform\Services\UserTicketCountService;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;

class ProcessDeveloperRoleChangeAction
{
    public function execute(
        User $user,
        ?string $developerRoleBefore,
        ?string $developerRoleAfter,
        ?User $actingUser = null,
    ): void {
        if ($developerRoleBefore === $developerRoleAfter) {
            return;
        }

        $permissionsName = Permissions::toString((int) $user->getAttribute('Permissions'));

        if ($developerRoleBefore === Role::DEVELOPER_JUNIOR && $developerRoleAfter === Role::DEVELOPER) {
            $this->activatePendingClaims($user, $permissionsName, $actingUser);
        }

        if ($developerRoleAfter === null) {
            $this->dropOpenClaims($user, $permissionsName, $actingUser);
            $this->releaseTicketClaims($user);
        }

        if ($developerRoleBefore === Role::DEVELOPER) {
            $this->expireActiveMaintainerships($user);
        }
    }

    private function activatePendingClaims(User $user, string $permissionsName, ?User $actingUser): void
    {
        $claimComment = $actingUser
            ? "{$actingUser->display_name} updated {$user->display_name}'s claim via promotion to {$permissionsName}."
            : "{$user->display_name}'s claim updated via promotion to {$permissionsName}.";
        $claimComment .= ' Claim Status: ' . ClaimStatus::Active->label();

        $user->achievementSetClaims()
            ->inReview()
            ->get()
            ->each(function (AchievementSetClaim $pendingClaim) use ($claimComment): void {
                $pendingClaim->status = ClaimStatus::Active;
                $pendingClaim->save();

                addArticleComment('Server', CommentableType::SetClaim, $pendingClaim->game_id, $claimComment);
            });
    }

    private function dropOpenClaims(User $user, string $permissionsName, ?User $actingUser): void
    {
        $user->achievementSetClaims()
            ->activeOrInReview()
            ->get()
            ->each(function (AchievementSetClaim $openClaim) use ($user, $permissionsName, $actingUser): void {
                $openClaim->status = ClaimStatus::Dropped;
                $openClaim->save();

                $claimKindLabel = $openClaim->claim_type->label();
                $claimComment = $actingUser
                    ? "{$actingUser->display_name} dropped {$user->display_name}'s {$claimKindLabel} claim via demotion to {$permissionsName}."
                    : "{$user->display_name}'s {$claimKindLabel} claim dropped via demotion to {$permissionsName}.";

                addArticleComment('Server', CommentableType::SetClaim, $openClaim->game_id, $claimComment);
            });
    }

    private function releaseTicketClaims(User $user): void
    {
        $claimedTickets = Ticket::query()
            ->where('assignee_id', $user->id)
            ->whereIn('state', [TicketState::Open, TicketState::Request, TicketState::Quarantined])
            ->withLiveTicketable()
            ->with([
                'assignee',
                'ticketable' => fn (MorphTo $ticketable) => $ticketable->morphWith([
                    Achievement::class => ['developer'],
                    Leaderboard::class => ['developer'],
                ]),
            ])
            ->get()
            ->filter(fn (Ticket $ticket): bool => $ticket->getTicketableModel()->getTicketableAssignee()?->id !== $user->id);

        if ($claimedTickets->isEmpty()) {
            return;
        }

        $serverAccount = User::whereName('Server')->firstOrFail();
        $releaseTicketClaimAction = new ReleaseTicketClaimAction();

        foreach ($claimedTickets as $claimedTicket) {
            $releaseTicketClaimAction->execute($claimedTicket, $serverAccount, isExpired: true);
        }
    }

    private function expireActiveMaintainerships(User $user): void
    {
        $maintainershipQuery = AchievementMaintainer::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->whereNull('effective_until');

        $maintainedAchievementIds = $maintainershipQuery->clone()->pluck('achievement_id');

        $maintainershipQuery->update([
            'is_active' => false,
            'effective_until' => now(),
        ]);

        if ($maintainedAchievementIds->isEmpty()) {
            return;
        }

        // Reassign tickets back to the triggerable's author.
        Ticket::query()
            ->forTicketableType(TicketableType::Achievement)
            ->whereIn('ticketable_id', $maintainedAchievementIds)
            ->where('assignee_id', $user->id)
            ->whereIn('state', [TicketState::Open, TicketState::Request, TicketState::Quarantined])
            ->update([
                'assignee_id' => DB::raw('(SELECT achievements.user_id FROM achievements WHERE achievements.id = tickets.ticketable_id)'),
            ]);

        // Eloquent bulk updates don't trigger events. We'll manually bust the caches ourselves.
        $achievementAuthorIds = Achievement::whereIn('id', $maintainedAchievementIds)->pluck('user_id');
        app(UserTicketCountService::class)->clearForUserIds($achievementAuthorIds->push($user->id)->unique());
    }
}
