<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Events\AchievementSetCompleted;
use App\Platform\Events\PlayerAchievementUnlocked;
use App\Platform\Models\Achievement;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerAchievement;
use App\Site\Models\User;
use Carbon\Carbon;

class UnlockPlayerAchievementAction
{
    public function execute(User $user, Achievement $achievement, bool $hardcore, ?User $unlockedBy = null): array
    {
        // TODO refactor to new schema

        $alreadyUnlocked = false;
        $response = ['achievementId' => $achievement->id];

        $unlock = $user->playerAchievements()->where('achievement_id', $achievement->id)->first();
        if ($unlock == null) {
            $unlock = new PlayerAchievement([
                'user_id' => $user->id,
                'achievement_id' => $achievement->id,
                // TODO assume trigger_id from most recent version of the achievement trigger
                // TODO 'trigger_id' => ???
            ]);
            $user->playerAchievements()->save($unlock);
        }

        // determine if the unlock needs to occur and update the player's score accordingly
        if ($hardcore) {
            if ($unlock->unlocked_hardcore_at != null) {
                $alreadyUnlocked = true;
            } else {
                $user->points_total += $achievement->points;
                $unlock->unlocked_hardcore_at = Carbon::now();

                if ($unlock->unlocked_at == null) {
                    $user->points_total += $achievement->points;
                    $unlock->unlocked_at = $unlock->unlocked_hardcore_at;
                }
            }
        } else {
            if ($unlock->unlocked_at != null) {
                $alreadyUnlocked = true;
            } else {
                $user->points_total += $achievement->points;
                $unlock->unlocked_at = Carbon::now();
            }
        }

        if (!$alreadyUnlocked) {
            // set the unlocked_by or associate to the player's session
            if ($unlockedBy) {
                $unlock->unlocker_id = $unlockedBy->id;
            } else {
                $playerSession = $user->playerSessions()->where('game_id', $achievement->game_id)->first();
                if ($playerSession) {
                    $unlock->player_session_id = $playerSession->id;
                }
            }

            // commit the unlock
            $unlock->save();

            // post the unlock notification
            PlayerAchievementUnlocked::dispatch($user, $achievement, $hardcore);

            /*
            * TODO: adjust retro ratio for user -> queue job via event
            */

            // commit the changes to the user's score
            $user->save();
        }

        $response['pointsTotal'] = $user->points_total;

        /** @var ?Game $game */
        $game = Game::find($achievement->game_id);
        if ($game) {
            // determine how many achievements are still needed for the user to complete/master the set
            $achievementIds = [];
            foreach ($game->achievements()->get() as $game_achievement) {
                /*
                * TODO: only capture Core achievements
                */
                $achievementIds[] = $game_achievement->id;
            }
            $coreCount = count($achievementIds);

            $userUnlocks = $user->playerAchievements()->whereIn('achievement_id', $achievementIds);

            if ($hardcore) {
                $response['achievementsRemaining'] = $coreCount - $userUnlocks->whereNotNull('unlocked_hardcore_at')->count();
            } else {
                $response['achievementsRemaining'] = $coreCount - $userUnlocks->whereNotNull('unlocked_at')->count();
            }
        }

        if ($alreadyUnlocked) {
            if ($hardcore) {
                $response['error'] = 'User already has hardcore and regular achievements awarded.';
            } else {
                $response['error'] = 'User already has this achievement awarded.';
            }

            $response['success'] = false;

            return $response;
        }

        // if the set has been completed, post the mastery notification
        if ($game && $response['achievementsRemaining'] == 0) {
            AchievementSetCompleted::dispatch($user, $game, $hardcore);
        }

        /*
         * TODO: count unlock for achievement author -> queue job via event
         */

        return $response;
    }
}
