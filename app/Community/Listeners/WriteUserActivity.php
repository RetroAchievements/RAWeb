<?php

declare(strict_types=1);

namespace App\Community\Listeners;

use App\Community\Models\UserActivity;
use App\Legacy\Models\User as LegacyUser;
use App\Platform\Events\AchievementCreated;
use App\Platform\Events\AchievementTriggerEdited;
use App\Platform\Events\PlayerCompletedAchievementSet;
use App\Platform\Events\PlayerGameAttached;
use App\Platform\Events\PlayerLeaderboardEntrySubmitted;
use App\Platform\Events\PlayerLeaderboardEntryUpdated;
use App\Platform\Events\PlayerUnlockedAchievement;
use App\Site\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login;

class WriteUserActivity
{
    /**
     * This will _only_ store UserActivity entries on users
     * Other side-effects should be handled in dedicated listeners
     */
    public function handle(object $event): void
    {
        $storeActivity = true;
        $activityTypeId = null;
        $subjectType = null;
        $subjectId = null;
        $context = null;

        /** @var User $user */
        $user = $event->user;

        if ($user instanceof LegacyUser) {
            return;
        }

        switch ($event::class) {
            case Login::class:
                /**
                 * login will only be called when user was logged out in-between
                 */
                $activityTypeId = UserActivity::Login;
                /**
                 * ignore login activity within 6 hours after the last login activity
                 */
                $storeActivity = $user->activities()
                    ->where('activity_type_id', '=', $activityTypeId)
                    ->where('created_at', '>', Carbon::now()->subHours(6))
                    ->doesntExist();
                break;
            case AchievementCreated::class:
                $activityTypeId = UserActivity::UploadAchievement;
                // TODO: data/data2
                $subjectType = 'achievement';
                break;
            case AchievementTriggerEdited::class:
                $activityTypeId = UserActivity::EditAchievement;
                // TODO: data/data2
                $subjectType = 'achievement';
                break;
            case PlayerLeaderboardEntrySubmitted::class:
                $activityTypeId = UserActivity::NewLeaderboardEntry;
                // TODO: data/data2
                $subjectType = 'leaderboard-entry';
                break;
            case PlayerLeaderboardEntryUpdated::class:
                $activityTypeId = UserActivity::ImprovedLeaderboardEntry;
                // TODO: data/data2
                $subjectType = 'leaderboard-entry';
                break;
            case PlayerUnlockedAchievement::class:
                $activityTypeId = UserActivity::UnlockedAchievement;
                // TODO: data/data2
                $subjectType = 'achievement';
                $subjectId = $event->achievement->id;
                $context = $event->hardcore ? 1 : null;
                break;
            case PlayerCompletedAchievementSet::class:
                $activityTypeId = UserActivity::CompleteGame;
                // TODO: data/data2
                $subjectType = 'game';
                break;
            case PlayerGameAttached::class:
                $activityTypeId = UserActivity::StartedPlaying;
                $subjectType = 'game';
                $subjectId = $event->game->id ?? null;
                $storeActivity = !empty($subjectId);
                break;
            default:
        }

        if ($activityTypeId && $storeActivity) {
            $user->activities()->save(new UserActivity([
                'activity_type_id' => $activityTypeId,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'subject_context' => $context,
            ]));
        }

        /*
         * update last activity timestamp regardless of whether an activity was stored as some might have been suppressed
         */
        $user->last_activity_at = Carbon::now();
        $user->save();
    }
}
