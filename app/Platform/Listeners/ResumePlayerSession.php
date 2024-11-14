<?php

namespace App\Platform\Listeners;

use App\Models\Game;
use App\Models\User;
use App\Platform\Actions\ResumePlayerSessionAction;
use App\Platform\Events\PlayerSessionHeartbeat;
use Illuminate\Contracts\Queue\ShouldQueue;

class ResumePlayerSession implements ShouldQueue
{
    public string $queue = 'player-sessions';

    public function handle(object $event): void
    {
        $user = null;
        $game = null;
        $message = null;
        $timestamp = null;
        $gameHash = null;
        $userAgent = null;
        $ipAddress = null;

        switch ($event::class) {
            case PlayerSessionHeartbeat::class:
                $user = $event->user;
                $game = $event->game;
                $message = $event->message;
                $timestamp = $event->timestamp;
                $userAgent = $event->userAgent;
                $ipAddress = $event->ipAddress;
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

        if (!$user instanceof User || !$game instanceof Game) {
            return;
        }

        app()->make(ResumePlayerSessionAction::class)
            ->execute(
                $user,
                $game,
                $gameHash,
                $message,
                $timestamp,
                $userAgent,
                $ipAddress,
            );
    }
}
