<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Connect\Actions\ResolveAchievementSetsAction;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\PlayerAchievementSet;
use App\Models\PlayerGame;
use App\Models\PlayerSession;
use App\Models\User;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Events\PlayerSessionResumed;
use App\Platform\Events\PlayerSessionStarted;
use App\Platform\Jobs\UpdatePlayerGameMetricsJob;
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
        $timestamp ??= Carbon::now();

        // upsert player game and update last played date right away
        $playerGame = app()->make(AttachPlayerGameAction::class)
            ->execute($user, $game);
        $playerGame->last_played_at = $timestamp;
        $playerGame->save();

        $isMultiDiscGameHash = $gameHash?->isMultiDiscGameHash();

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
            $user->save();
        }

        // if the session is less than 10 minutes old, resume session
        if ($playerSession && ($timestamp->diffInMinutes($playerSession->rich_presence_updated_at, true) < 10)) {
            $newDuration = max(1, (int) $timestamp->diffInMinutes($playerSession->created_at, true));
            // duration is in minutes, playtimes are in seconds.
            $adjustment = ($newDuration - $playerSession->duration) * 60;
            if ($adjustment > 0) {
                $playerSession->duration = $newDuration;

                if (!$playerGame->playtime_total) {
                    // no playtime metrics exist - generate them
                    $playerSession->save(); // ensure job uses updated duration
                    dispatch(new UpdatePlayerGameMetricsJob($user->id, $game->id));
                } else {
                    // extending an existing session. attempt to keep the playtime metrics
                    // up to date without doing a full regeneration. a full regeneration
                    // will occur after the next unlock.
                    $this->extendPlayTime($playerGame, $playerSession, $gameHash, $adjustment);
                }
            }

            if ($presence) {
                $playerSession->rich_presence = $presence;

                // TODO deprecated, read from last player_sessions entry where needed
                $user->RichPresenceMsg = utf8_sanitize($presence);
                $user->RichPresenceMsgDate = Carbon::now();
                $user->save();
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
        $user->save();

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

    private function extendPlayTime(PlayerGame $playerGame, PlayerSession $playerSession, ?GameHash $gameHash, int $adjustment): void
    {
        $playerGame->playtime_total += $adjustment;
        $playerGame->save();

        // also update related achievement_sets
        $activeAchievementSets = [];
        if ($gameHash) {
            $resolvedSets = (new ResolveAchievementSetsAction())->execute($gameHash, $playerGame->user);
            foreach ($resolvedSets as $resolvedSet) {
                $activeAchievementSets[] = $resolvedSet->id;
            }
        }
        if (empty($resolvedSets)) {
            $coreSet = $playerGame->game->gameAchievementSets->where('type', AchievementSetType::Core)->first();
            if ($coreSet) {
                $activeAchievementSets[] = $coreSet->id;
            }
        }

        if (!empty($activeAchievementSets)) {
            $playerAchievementSets = PlayerAchievementSet::whereIn('achievement_set_id', $activeAchievementSets)->get();
            foreach ($playerAchievementSets as $playerAchievementSet) {
                $playerAchievementSet->time_taken += $adjustment;

                if ($playerSession->hardcore || $playerGame->user->RAPoints > $playerGame->user->RASoftcorePoints) {
                    $playerAchievementSet->time_taken_hardcore += $adjustment;
                }

                $playerAchievementSet->save();
            }
        }
    }
}
