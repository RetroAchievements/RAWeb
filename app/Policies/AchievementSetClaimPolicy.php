<?php

declare(strict_types=1);

namespace App\Policies;

use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AchievementSetClaimPolicy
{
    use HandlesAuthorization;

    public function manage(User $user): bool
    {
        return $user->hasAnyRole([
            Role::DEVELOPER_JUNIOR,
            Role::DEVELOPER,
            Role::MODERATOR,
        ]);
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, AchievementSetClaim $achievementSetClaim): bool
    {
        return true;
    }

    public function create(User $user, ?Game $game = null): bool
    {
        // First, check the basic role requirement.
        if (!$user->hasAnyRole([Role::DEVELOPER, Role::DEVELOPER_JUNIOR])) {
            return false;
        }

        // If no game is provided, we'll just be happy the user passes role verification.
        if (!$game) {
            return true;
        }

        // Junior developers can only create claims for games with forum topics.
        if ($user->hasRole(Role::DEVELOPER_JUNIOR) && !$game->ForumTopicID) {
            return false;
        }

        // If the user already has a claim on this game, allow it (for extensions).
        $existingClaim = $game->achievementSetClaims()
            ->activeOrInReview()
            ->where('user_id', $user->id)
            ->exists();

        if ($existingClaim) {
            return true;
        }

        // Determine max claims based on role.
        $maxClaims = AchievementSetClaim::getMaxClaimsForUser($user);

        $activeClaimCount = once(fn () => getActiveClaimCount($user, false, false));
        $isSoleAuthor = once(fn () => checkIfSoleDeveloper($user, $game->id));

        // The user can create a claim if they have claims remaining OR they're the sole author.
        return ($activeClaimCount < $maxClaims) || $isSoleAuthor;
    }

    public function updateAny(User $user): bool
    {
        // Admins and moderators have the ability to update any claim.
        if ($user->hasAnyRole([Role::ADMINISTRATOR, Role::MODERATOR])) {
            return true;
        }

        return false;
    }

    public function update(User $user, AchievementSetClaim $achievementSetClaim): bool
    {
        // Admins and moderators need the ability to fully modify the various fields of a claim.
        if ($this->updateAny($user)) {
            return true;
        }

        // Users can only complete their own claims (extensions use the `create` policy).
        // User can't update their own claim if the claim is in review status.
        return
            $achievementSetClaim->user_id === $user->id
            && $achievementSetClaim->Status !== ClaimStatus::InReview;
    }

    public function delete(User $user, AchievementSetClaim $achievementSetClaim): bool
    {
        // Users can only drop their own claims (as long as they're not in review status).
        return
            $achievementSetClaim->user_id === $user->id
            && $achievementSetClaim->Status !== ClaimStatus::InReview;
    }

    public function review(User $user, AchievementSetClaim $achievementSetClaim): bool
    {
        // When a claim is in review status, it's effectively locked.
        // A user can toggle a claim's review status if:
        // 1. The user has either the CODE_REVIEWER or MODERATOR role.
        // 2. It's not the user's own claim.
        // 3. It's a primary claim.
        // 4. The claim owner has the DEVELOPER_JUNIOR role.
        return
            $user->hasAnyRole([Role::CODE_REVIEWER, Role::MODERATOR])
            && $achievementSetClaim->user_id !== $user->id
            && $achievementSetClaim->ClaimType === ClaimType::Primary
            && $achievementSetClaim->user->hasRole(Role::DEVELOPER_JUNIOR);
    }

    public function complete(User $user, AchievementSetClaim $achievementSetClaim): bool
    {
        // The user must own the claim.
        if ($achievementSetClaim->user_id !== $user->id) {
            return false;
        }

        // The claim cannot be in review status.
        if ($achievementSetClaim->Status === ClaimStatus::InReview) {
            return false;
        }

        $game = $achievementSetClaim->game;

        // For valid/active systems, require published/official achievements to complete the claim.
        if (isValidConsoleId($game->ConsoleID)) {
            return $game->achievements_published > 0; // TODO this probably needs to use achievement sets at some point in the future
        }

        // Keep in mind, inactive systems will land here.
        // In practice, this doesn't matter because developers can't
        // promote achievements for inactive systems anyway.
        return true;
    }

    public function restore(User $user, AchievementSetClaim $achievementSetClaim): bool
    {
        return false;
    }

    public function forceDelete(User $user, AchievementSetClaim $achievementSetClaim): bool
    {
        return false;
    }
}
