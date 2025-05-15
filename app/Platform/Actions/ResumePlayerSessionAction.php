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

class ResumePlayerSessionAction
{
    public function execute(
        User $user,
        Game $game,
        ?GameHash $gameHash = null,
        ?string $presence = null,
        ?Carbon $timestamp = null,
        ?string $userAgent = null,
        ?string $ipAddress = null,
    ): PlayerSession {
        // upsert player game and update last played date right away
        $playerGame = app()->make(AttachPlayerGameAction::class)
            ->execute($user, $game);
        $playerGame->last_played_at = $timestamp;
        $playerGame->save();

        $isMultiDiscGameHash = $gameHash?->isMultiDiscGameHash();

        $timestamp ??= Carbon::now();

        // look for an active session
        /** @var ?PlayerSession $playerSession */
        $playerSession = $user->playerSessions()
            ->where('game_id', $game->id)
            ->where(function ($query) use ($gameHash, $isMultiDiscGameHash) {
                if ($gameHash && !$isMultiDiscGameHash) {
                    $query->where('game_hash_id', $gameHash->id)
                        ->orWhereNull('game_hash_id');
                }
            })
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
            $user->saveQuietly();
        }

        // if the session is less than 10 minutes old, resume session
        if ($playerSession && ($timestamp->diffInMinutes($playerSession->rich_presence_updated_at, true) < 10)) {
            $playerSession->duration = max(1, (int) $timestamp->diffInMinutes($playerSession->created_at, true));

            if ($presence) {
                $playerSession->rich_presence = $presence;

                // TODO deprecated, read from last player_sessions entry where needed
                $user->RichPresenceMsg = utf8_sanitize($presence);
                $user->RichPresenceMsgDate = Carbon::now();
                $user->saveQuietly();
            }
            $playerSession->rich_presence_updated_at = $timestamp > $playerSession->rich_presence_updated_at ? $timestamp : $playerSession->rich_presence_updated_at;

            if ($gameHash && !$playerSession->game_hash_id && !$isMultiDiscGameHash) {
                $playerSession->game_hash_id = $gameHash->id;
            }

            if ($userAgent && !$playerSession->user_agent) {
                $playerSession->user_agent = $userAgent;
            }

            if ($ipAddress && !$playerSession->ip_address) {
                $playerSession->ip_address = $ipAddress;
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
        $user->saveQuietly();

        // create new session
        $playerSession = new PlayerSession([
            'user_id' => $user->id,
            'game_id' => $game->id,
            'game_hash_id' => $gameHash?->id,
            // TODO add game hash set reference as soon as they are in place
            // 'game_hash_set_id' => $game->gameHashSets()->first()->id, // TODO
            'rich_presence' => $presence,
            'rich_presence_updated_at' => $timestamp,
            'duration' => 1, // 1 minute is minimum duration
            'user_agent' => $userAgent,
            'ip_address' => $ipAddress,
        ]);

        $user->playerSessions()->save($playerSession);

        PlayerSessionStarted::dispatch($user, $game, $presence);

        return $playerSession;
    }
}
