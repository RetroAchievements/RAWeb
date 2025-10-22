<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Data\AchievementSetClaimData;
use App\Platform\Data\GamePageClaimData;
use App\Platform\Enums\AchievementFlag;
use Illuminate\Support\Collection;

class BuildGamePageClaimDataAction
{
    /**
     * @param Collection<int, AchievementSetClaim> $achievementSetClaims
     */
    public function execute(Game $game, ?User $user, Collection $achievementSetClaims): ?GamePageClaimData
    {
        // If the user is unauthenticated or they don't have a Developer/JrDev role, bail.
        if (!$user || !$user->hasAnyRole([Role::DEVELOPER, Role::DEVELOPER_JUNIOR])) {
            return null;
        }

        // Get the user's claim on this game, we'll need it for a bunch of stuff later.
        $userClaim = $achievementSetClaims
            ->whereIn('Status', [ClaimStatus::Active, ClaimStatus::InReview])
            ->where('user_id', $user->id)
            ->first();

        // Check if there's an active primary claim by another user.
        $primaryClaimByOtherUser = $achievementSetClaims
            ->whereIn('Status', [ClaimStatus::Active, ClaimStatus::InReview])
            ->where('ClaimType', ClaimType::Primary)
            ->where('user_id', '!=', $user->id)
            ->first();

        // Determine the user's max number of claims, based on their developer role.
        $maxClaimCount = AchievementSetClaim::getMaxClaimsForUser($user);

        // Calculate if the user is the sole author of all existing achievements for the set.
        $isSoleAuthor = once(fn () => checkIfSoleDeveloper($user, $game->id));

        // Calculate if the game has official/published achievements and if the system is valid (rolled out).
        $hasOfficialAchievements = $this->calculateHasOfficialAchievements($game);
        $isValidConsole = isValidConsoleId($game->ConsoleID);

        $wouldBeCollaboration = $primaryClaimByOtherUser !== null;
        $wouldBeRevision = $game->achievements_published > 0;

        return new GamePageClaimData(
            userClaim: $userClaim
                ? AchievementSetClaimData::fromAchievementSetClaim(
                    $userClaim,
                    $isValidConsole,
                    $hasOfficialAchievements
                )->include(
                    'isExtendable',
                    'isDroppable',
                    'extensionsCount',
                    'isCompletable',
                    'minutesActive',
                    'minutesLeft',
                )
                : null,

            doesPrimaryClaimExist: $primaryClaimByOtherUser !== null,
            isSoleAuthor: $isSoleAuthor,
            maxClaimCount: $maxClaimCount,
            numClaimsRemaining: $this->calculateNumClaimsRemaining($user, $maxClaimCount),
            numUnresolvedTickets: Ticket::forDeveloper($user)->awaitingDeveloper()->count(),
            wouldBeCollaboration: $wouldBeCollaboration,
            wouldBeRevision: $wouldBeRevision,
        );
    }

    private function calculateNumClaimsRemaining(User $user, int $maxClaims): int
    {
        $activeClaimCount = once(fn () => getActiveClaimCount($user, false, false));
        $remaining = $maxClaims - $activeClaimCount;

        return max(0, $remaining);
    }

    private function calculateHasOfficialAchievements(Game $game): bool
    {
        // Check if any achievement for the set is published.
        foreach ($game->gameAchievementSets as $gameAchievementSet) {
            foreach ($gameAchievementSet->achievementSet->achievements as $achievement) {
                if ($achievement->Flags === AchievementFlag::OfficialCore->value) {
                    return true;
                }
            }
        }

        return false;
    }
}
