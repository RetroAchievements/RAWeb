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
        $maxClaims = $this->calculateMaxClaimCount($user);

        // Calculate if the user is the sole author of all existing achievements for the set.
        $isSoleAuthor = $this->calculateIsSoleAuthor($game, $user);

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
            maxClaimCount: $maxClaims,
            numClaimsRemaining: $this->calculateNumClaimsRemaining($user, $maxClaims),
            numUnresolvedTickets: Ticket::forDeveloper($user)->unresolved()->count(),
            wouldBeCollaboration: $wouldBeCollaboration,
            wouldBeRevision: $wouldBeRevision,
        );
    }

    private function calculateMaxClaimCount(User $user): int
    {
        // Junior developers get 1 claim. Full developers get 4 claims.
        if ($user->hasRole(Role::DEVELOPER_JUNIOR)) {
            return 1;
        } elseif ($user->hasRole(Role::DEVELOPER)) {
            return 4;
        }

        return 0;
    }

    private function calculateNumClaimsRemaining(User $user, int $maxClaims): int
    {
        $activeClaimCount = getActiveClaimCount($user, true, false);
        $remaining = $maxClaims - $activeClaimCount;

        return max(0, $remaining);
    }

    private function calculateIsSoleAuthor(Game $game, User $user): bool
    {
        // If the game has no achievements, the user can't be the sole author.
        if ($game->achievements_published === 0) {
            return false;
        }

        // Collect all unique developer IDs from published achievements.
        $developerIds = collect();
        foreach ($game->gameAchievementSets as $gameAchievementSet) {
            foreach ($gameAchievementSet->achievementSet->achievements as $achievement) {
                if ($achievement->Flags === AchievementFlag::OfficialCore->value) {
                    $developerIds->add($achievement->user_id);
                }
            }
        }

        $uniqueDeveloperIds = $developerIds->unique();

        // If the user is the only developer, they're the sole author.
        return $uniqueDeveloperIds->count() === 1 && $uniqueDeveloperIds->first() === $user->id;
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
