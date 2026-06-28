<?php

declare(strict_types=1);

namespace App\Policies;

use App\Community\Enums\Rank;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\Role;
use App\Models\User;
use App\Platform\Enums\GameScreenshotStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

class GameScreenshotPolicy
{
    use HandlesAuthorization;

    public const REVIEWER_ROLES = [
        Role::ROOT,
        Role::ADMINISTRATOR,
        Role::MODERATOR,
        Role::GAME_EDITOR,
        Role::MEDIA_EDITOR,
    ];

    public function manage(User $user): bool
    {
        if ($user->hasAnyRole(self::REVIEWER_ROLES)) {
            return true;
        }

        return $this->canDeveloperReviewAnyGame($user);
    }

    public function create(User $user, Game $game): bool
    {
        // non-devs shouldn't contribute screenshots for games being worked on by a dev
        if ($game->has_active_or_in_review_claims) {
            return false;
        }

        // "subset games" shouldn't have dedicated screenshot galleries
        if ($game->is_subset_game) {
            return false;
        }

        // retired games (~Z~ prefix) shouldn't accept new screenshots
        if (str_starts_with($game->title, '~Z~')) {
            return false;
        }

        if (!$user->hasRole(Role::ROOT) && !config('feature.game_screenshot_uploads')) {
            return false;
        }

        if (!$user->hasRole(Role::ROOT) && !$user->enable_beta_features) {
            return false;
        }

        if ($game->is_media_restricted) {
            return false;
        }

        if ($user->isBanned() || $user->isMuted()) {
            return false;
        }

        if ($user->unranked_at !== null && !$user->hasAnyRole([Role::DEVELOPER, Role::DEVELOPER_JUNIOR])) {
            return false;
        }

        if (!$user->hasVerifiedEmail()) {
            return false;
        }

        // The user must have enough points and their account must be old enough.
        $hasEnoughPoints = $user->points_hardcore >= Rank::MIN_POINTS || $user->points >= Rank::MIN_POINTS;
        $isOldEnough = $user->created_at && $user->created_at->diffInDays(now()) >= 30;

        return $hasEnoughPoints && $isOldEnough;
    }

    public function delete(User $user, GameScreenshot $screenshot): bool
    {
        return
            $screenshot->captured_by_user_id === $user->id
            && $screenshot->status === GameScreenshotStatus::Pending;
    }

    public function forceDelete(User $user, GameScreenshot $screenshot): bool
    {
        // Only rejected screenshots may be permanently deleted.
        if ($screenshot->status !== GameScreenshotStatus::Rejected) {
            return false;
        }

        return $user->hasAnyRole([
            Role::DEVELOPER,
            Role::GAME_EDITOR,
            Role::MEDIA_EDITOR,
            Role::MODERATOR,
        ]);
    }

    public function review(User $user, GameScreenshot $screenshot): bool
    {
        if ($user->hasAnyRole(self::REVIEWER_ROLES)) {
            return true;
        }

        return $this->canDeveloperReviewGame($user, $screenshot->game_id);
    }

    /**
     * TODO right now this is scoped to a developer's own games.
     * this should maybe be a filter, where devs can help out
     * with games they've worked on (primarily), or optionally
     * everything else. let's collect some data on how this feature
     * evolves first before making that decision.
     */
    private function canDeveloperReviewAnyGame(User $user): bool
    {
        if (!$user->hasRole(Role::DEVELOPER)) {
            return false;
        }

        return Achievement::query()
            ->where('user_id', $user->id)
            ->where('is_promoted', true)
            ->exists();
    }

    private function canDeveloperReviewGame(User $user, int $gameId): bool
    {
        if (!$user->hasRole(Role::DEVELOPER)) {
            return false;
        }

        return Achievement::query()
            ->where('game_id', $gameId)
            ->where('user_id', $user->id)
            ->where('is_promoted', true)
            ->exists();
    }
}
