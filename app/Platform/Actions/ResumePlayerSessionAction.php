<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Platform\Events\PlayerSessionResumed;
use App\Platform\Events\PlayerSessionStarted;
use App\Platform\Models\Game;
use App\Platform\Models\GameHash;
use App\Platform\Models\PlayerSession;
use App\Site\Models\User;
use Illuminate\Support\Carbon;

class ResumePlayerSessionAction
{
    public function execute(
        User $user,
        Game $game,
        ?GameHash $gameHash = null,
        ?string $presence = null,
        ?Carbon $timestamp = null,
    ): PlayerSession {
        // upsert player game and update last played date right away
        $attachPlayerGameAction = app()->make(AttachPlayerGameAction::class);
        $game = $attachPlayerGameAction->execute($user, $game);
        $playerGame = $game->pivot;
        $playerGame->last_played_at = $timestamp;
        $playerGame->save();

        $timestamp ??= Carbon::now();

        // look for an active session
        /** @var ?PlayerSession $playerSession */
        $playerSession = $user->playerSessions()
            ->where('game_id', $game->id)
            ->orderByDesc('id')
            ->first();

        if ($playerSession) {
            // if the session hasn't been updated in the last 10 minutes, start a new session
            if ($timestamp->diffInMinutes($playerSession->updated_at) < 10) {
                $playerSession->duration = max(1, $timestamp->diffInMinutes($playerSession->created_at));

                if ($presence) {
                    $playerSession->rich_presence = $presence;
                    $playerSession->rich_presence_updated_at = $timestamp;
                }

                $playerSession->save(['touch' => true]);

                PlayerSessionResumed::dispatch($user, $game, $presence);

                return $playerSession;
            }
        }

        // provide a default presence for the new session if none was provided
        if (!$presence) {
            $presence = 'Playing ' . $game->title;
        }

        // create new session
        $playerSession = new PlayerSession([
            'user_id' => $user->id,
            'game_id' => $game->id,
            // TODO add game hash set reference as soon as they are in place
            // 'game_hash_id' => $game->gameHashSets()->first()->hashes()->first()->id,
            // 'game_hash_set_id' => $game->gameHashSets()->first()->id, // TODO
            'rich_presence' => $presence,
            'rich_presence_updated_at' => $timestamp,
            'duration' => 1, // 1 minute is minimum duration
        ]);

        $user->playerSessions()->save($playerSession);

        // TODO: store user agent

        PlayerSessionStarted::dispatch($user, $game, $presence);

        return $playerSession;
    }
}
