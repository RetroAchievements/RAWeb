<?php

declare(strict_types=1);

namespace App\Policies;

use App\Community\Enums\Rank;
use App\Models\Game;
use App\Models\GameScreenshot;
use App\Models\Role;
use App\Models\User;
use App\Platform\Enums\GameScreenshotStatus;
use Illuminate\Auth\Access\HandlesAuthorization;

class GameScreenshotPolicy
{
    use HandlesAuthorization;

    public function create(User $user, Game $game): bool
    {
        if (!$user->hasRole(Role::ROOT) && !config('feature.game_screenshot_uploads')) {
            return false;
        }

        if ($game->is_media_restricted) {
            return false;
        }

        if ($user->isBanned() || $user->isMuted()) {
            return false;
        }

        if (!$user->hasVerifiedEmail()) {
            return false;
        }

        // Either the user has enough points or their account is old enough.
        $hasEnoughPoints = $user->points_hardcore >= Rank::MIN_POINTS || $user->points >= Rank::MIN_POINTS;
        $isOldEnough = $user->created_at && $user->created_at->diffInDays(now()) >= 14;

        return $hasEnoughPoints || $isOldEnough;
    }

    public function delete(User $user, GameScreenshot $screenshot): bool
    {
        return
            $screenshot->captured_by_user_id === $user->id
            && $screenshot->status === GameScreenshotStatus::Pending;
    }
}
