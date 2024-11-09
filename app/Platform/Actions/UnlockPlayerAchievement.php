<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\GameHash;
use App\Models\System;
use App\Models\User;
use App\Platform\Events\PlayerAchievementUnlocked;
use App\Platform\Jobs\UnlockPlayerAchievementJob;
use Carbon\Carbon;
use Exception;

class UnlockPlayerAchievement
{
    public function execute(
        User $user,
        Achievement $achievement,
        bool $hardcore,
        ?Carbon $timestamp = null,
        ?User $unlockedBy = null,
        ?GameHash $gameHash = null,
    ): void {
        $timestamp ??= Carbon::now();

        $achievement->loadMissing('game.system');
        if (!$achievement->game) {
            throw new Exception('Achievement does not belong to any game');
        }

        if ($gameHash?->isMultiDiscGameHash()) {
            $gameHash = null;
        }

        // also unlock active event achievements associated to the achievement being unlocked
        if ($hardcore) {
            foreach ($achievement->eventAchievements()->active($timestamp)->get() as $eventAchievement) {
                dispatch(new UnlockPlayerAchievementJob($user->id, $eventAchievement->achievement_id, true, $timestamp, $unlockedBy?->id, $gameHash?->id))
                    ->onQueue('player-achievements');
            }
        }

        $playerSession = null;
        if ($unlockedBy || !System::isGameSystem($achievement->game->system->id)) {
            // if it's a manual unlock or a non-game achievement, attach the game
            // but don't generate a session.
            app()->make(AttachPlayerGame::class)
                ->execute($user, $achievement->game);
        } else {
            // make sure to resume the player session which will attach the game to the player, too
            $playerSession = app()->make(ResumePlayerSession::class)
                ->execute($user, $achievement->game, gameHash: $gameHash, timestamp: $timestamp);
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
        if ($playerSession) {
            $unlock->player_session_id = $playerSession->id;

            $playerSession->hardcore = $playerSession->hardcore ?: (bool) $unlock->unlocked_hardcore_at;
            $playerSession->save();
        }

        // commit the unlock
        if ($achievement->is_published) {
            $unlock->save();

            // post the unlock notification
            PlayerAchievementUnlocked::dispatch($user, $achievement, $hardcore);
        }
    }
}
