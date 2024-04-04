<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameHash;
use App\Models\PlayerSession;
use App\Models\User;
use App\Platform\Events\PlayerSessionResumed;
use App\Platform\Events\PlayerSessionStarted;
use Carbon\Carbon;

class ResumePlayerSession
{
    public function execute(
        User $user,
        Game $game,
        ?GameHash $gameHash = null,
        ?string $presence = null,
        ?Carbon $timestamp = null,
        ?string $userAgent = null,
        ?string $ipAddr = null,
    ): PlayerSession {
        // upsert player game and update last played date right away
        $playerGame = app()->make(AttachPlayerGame::class)
            ->execute($user, $game);
        $playerGame->last_played_at = $timestamp;
        $playerGame->save();

        $timestamp ??= Carbon::now();

        // look for an active session
        /** @var ?PlayerSession $playerSession */
        $playerSession = $user->playerSessions()
            ->where('game_id', $game->id)
            ->where(function ($query) use ($userAgent) {
                if ($userAgent) {
                    $query->where('user_agent', $userAgent)
                        ->orWhereNull('user_agent');
                }
            })
            ->orderByDesc('id')
            ->first();

        if ($user->LastGameID !== $game->id) {
            expireRecentlyPlayedGames($user->User);
            // TODO deprecated, read from last player_sessions entry where needed
            $user->LastGameID = $game->id;
            $user->save();
        }

        // if the session is less than 10 minutes old, resume session
        if ($playerSession && $timestamp->diffInMinutes($playerSession->rich_presence_updated_at) < 10) {
            $playerSession->duration = max(1, $timestamp->diffInMinutes($playerSession->created_at));

            if ($presence) {
                $playerSession->rich_presence = $presence;

                // TODO deprecated, read from last player_sessions entry where needed
                $user->RichPresenceMsg = utf8_sanitize($presence);
                $user->RichPresenceMsgDate = Carbon::now();
                $user->save();
            }
            $playerSession->rich_presence_updated_at = $timestamp > $playerSession->rich_presence_updated_at ? $timestamp : $playerSession->rich_presence_updated_at;

            if ($userAgent && !$playerSession->user_agent) {
                $playerSession->user_agent = $userAgent;
            }

            if ($ipAddr && !$playerSession->ip_addr) {
                $playerSession->ip_addr = $ipAddr;
            }

            $playerSession->save(['touch' => true]);

            PlayerSessionResumed::dispatch($user, $game, $presence);

            return $playerSession;
        }

        // provide a default presence for the new session if none was provided
        if (!$presence) {
            $presence = 'Playing ' . $game->title;
        }

        // TODO deprecated, read from last player_sessions entry where needed
        $user->RichPresenceMsg = utf8_sanitize($presence);
        $user->RichPresenceMsgDate = Carbon::now();
        $user->save();

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
            'user_agent' => $userAgent,
            'ip_addr' => $ipAddr,
        ]);

        $user->playerSessions()->save($playerSession);

        PlayerSessionStarted::dispatch($user, $game, $presence);

        return $playerSession;
    }
}
