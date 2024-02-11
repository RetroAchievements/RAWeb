<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\User;
use App\Platform\Events\PlayerAchievementUnlocked;
use Carbon\Carbon;
use Exception;

class UnlockPlayerAchievement
{
    public function execute(
        User $user,
        Achievement $achievement,
        bool $hardcore,
        ?Carbon $timestamp = null,
        ?User $unlockedBy = null
    ): void {
        $timestamp ??= Carbon::now();

        $achievement->loadMissing('game');
        if (!$achievement->game) {
            throw new Exception('Achievement does not belong to any game');
        }

        if ($unlockedBy) {
            // only attach the game if it's a manual unlock
            app()->make(AttachPlayerGame::class)
                ->execute($user, $achievement->game);
        } else {
            // make sure to resume the player session which will attach the game to the player, too
            $playerSession = app()->make(ResumePlayerSession::class)
                ->execute($user, $achievement->game, timestamp: $timestamp);
        }

        $unlock = $user->playerAchievements()->firstOrCreate([
            'achievement_id' => $achievement->id,
            // TODO 'trigger_id' => assume trigger_id from most recent version of the achievement trigger
        ]);

        // determine if the unlock needs to occur
        $alreadyUnlockedInThisMode = false;
        if ($hardcore) {
            if ($unlock->unlocked_hardcore_at !== null) {
                $alreadyUnlockedInThisMode = true;
            } else {
                $unlock->unlocked_hardcore_at = $timestamp;

                if ($unlock->wasRecentlyCreated) {
                    $unlock->unlocked_at = $unlock->unlocked_hardcore_at;
                }
            }
        } else {
            if (!$unlock->wasRecentlyCreated) {
                $alreadyUnlockedInThisMode = true;
            } else {
                $unlock->unlocked_at = $timestamp;
            }
        }

        if ($alreadyUnlockedInThisMode) {
            return;
        }

        // set the unlocked_by, reset if unlocked by player
        $unlock->unlocker_id = $unlockedBy?->id;

        // attach latest player session if it was not a manual unlock
        if (!$unlockedBy) {
            $unlock->player_session_id = $playerSession->id;

            $playerSession->hardcore = $playerSession->hardcore ?: (bool) $unlock->unlocked_hardcore_at;
            $playerSession->save();
        }

        // commit the unlock
        if ($achievement->is_published) {
            $unlock->save();
        }

        // post the unlock notification
        PlayerAchievementUnlocked::dispatch($user, $achievement, $hardcore);
    }
}
