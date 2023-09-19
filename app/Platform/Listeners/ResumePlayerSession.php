<?php

namespace App\Platform\Listeners;

use App\Platform\Actions\ResumePlayerSession as ResumePlayerSessionAction;
use App\Platform\Events\PlayerSessionHeartbeat;
use App\Platform\Models\Game;
use App\Site\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;

class ResumePlayerSession implements ShouldQueue
{
    public function handle(object $event): void
    {
        $user = null;
        $game = null;
        $message = null;
        $timestamp = null;
        $gameHash = null;

        switch ($event::class) {
            case PlayerSessionHeartbeat::class:
                $user = $event->user;
                $game = $event->game;
                $message = $event->message;
                $timestamp = $event->timestamp;
                // temp fix for PHPStan always-read-written-properties
                $gameHash = $event->gameHash;
                // TODO GameHash::where('hash', $this->gameHash)->firstOrFail(),
                break;
            // NOTE ResumePlayerSessionAction is executed synchronously during PlayerAchievementUnlockAction
            // case PlayerAchievementUnlocked::class:
            //     $achievement = $event->achievement;
            //     $game = $achievement->game;
            //     $user = $event->user;
            //     $timestamp = $event->timestamp;
            //     break;
        }

        if (!$game instanceof Game && is_int($game)) {
            $game = Game::find($game);
        }

        if (!$user instanceof User) {
            if (is_string($user)) {
                $user = User::firstWhere('User', $user);
            } elseif (is_int($user)) {
                $user = User::find($user);
            }
        }

        if (!$user || !$game) {
            return;
        }

        app()->make(ResumePlayerSessionAction::class)
            ->execute(
                $user,
                $game,
                $gameHash,
                $message,
                $timestamp,
            );
    }
}
