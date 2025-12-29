<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Connect\Actions\ResolveAchievementSetsAction;
use App\Models\Game;
use App\Models\GameHash;
use App\Models\GameRecentPlayer;
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
        $sessionKeepAliveTimeInMinutes = 10;
        $isBackdated = ($timestamp && $timestamp->diffInMinutes(Carbon::now(), true) > $sessionKeepAliveTimeInMinutes);

        $timestamp ??= Carbon::now();

        /** @var ?PlayerSession $playerSession */
        $playerSession = null;

        // upsert player game and update last played date right away
        $playerGame = app()->make(AttachPlayerGameAction::class)
            ->execute($user, $game);
        if (!$playerGame->last_played_at || $timestamp > $playerGame->last_played_at) {
            $playerGame->last_played_at = $timestamp;
            $playerGame->save();
        }

        $isMultiDiscGameHash = $gameHash?->isMultiDiscGameHash();

        if ($isBackdated) {
            // timestamp is more than 10 minutes in the past, find the newest session it might
            // belong to. don't worry about matching hash or user agent.
            $playerSession = $user->playerSessions()
                ->where('game_id', $game->id)
                ->where('created_at', '<', $timestamp)
                ->orderByDesc('id')
                ->first();
        } else {
            // look for an active session
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
        }

        // As best as we can, we'll try to only write once to the user model.
        $doesUserNeedsUpdate = false;

        if ($user->last_game_id !== $game->id) {
            $user->last_game_id = $game->id;
            $doesUserNeedsUpdate = true;
        }

        if ($playerSession) {
            // if the session was last updated less than 10 minutes ago, extend it.
            $newDuration = max(1, (int) $timestamp->diffInMinutes($playerSession->created_at, true));
            if ($isBackdated || $newDuration - $playerSession->duration < $sessionKeepAliveTimeInMinutes) {
                if ($newDuration > $playerSession->duration) {
                    // duration is in minutes, playtimes are in seconds.
                    $adjustment = ($newDuration - $playerSession->duration) * 60;

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

                if ($timestamp > $playerSession->rich_presence_updated_at) {
                    // rich_presence_updated_at is used to generate playtime stats in
                    // seconds as duration only captures minutes. so update it even if
                    // the rich_presence message isn't being updated.
                    $playerSession->rich_presence_updated_at = $timestamp;

                    if ($presence) {
                        $presence = utf8_sanitize($presence);

                        $playerSession->rich_presence = $presence;

                        if (!$isBackdated) {
                            // TODO deprecated, read from last player_sessions entry where needed
                            $user->rich_presence = $presence;
                            $user->rich_presence_updated_at = $timestamp;
                            $doesUserNeedsUpdate = true;

                            // Update the player's game_recent_players table entry.
                            GameRecentPlayer::upsert(
                                [
                                    'game_id' => $game->id,
                                    'user_id' => $user->id,
                                    'rich_presence' => $presence,
                                    'rich_presence_updated_at' => $timestamp,
                                ],
                                ['game_id', 'user_id'],
                                ['rich_presence', 'rich_presence_updated_at']
                            );
                        }
                    }
                }

                if (!$isBackdated) {
                    if ($gameHash && !$playerSession->game_hash_id && !$isMultiDiscGameHash) {
                        $playerSession->game_hash_id = $gameHash->id;
                    }

                    if ($userAgent && !$playerSession->user_agent) {
                        $playerSession->user_agent = $userAgent;
                    }

                    if ($ipAddress && !$playerSession->ip_address) {
                        $playerSession->ip_address = $ipAddress;
                    }
                }

                $playerSession->save(['touch' => true]);

                if ($doesUserNeedsUpdate) {
                    $user->saveQuietly();
                }

                if (!$isBackdated) {
                    PlayerSessionResumed::dispatch($user, $game, $presence);
                }

                return $playerSession;
            }
        }

        // provide a default presence for the new session if none was provided
        if (!$presence) {
            $presence = 'Playing ' . $game->title;
        }

        // TODO deprecated, read from last player_sessions entry where needed
        $user->rich_presence = utf8_sanitize($presence);
        $user->rich_presence_updated_at = Carbon::now();
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

        $playerSession->created_at = $timestamp;
        $playerSession->save();

        // Update the player's game_recent_players table entry.
        GameRecentPlayer::upsert(
            [
                'game_id' => $game->id,
                'user_id' => $user->id,
                'rich_presence' => $presence,
                'rich_presence_updated_at' => $timestamp,
            ],
            ['game_id', 'user_id'],
            ['rich_presence', 'rich_presence_updated_at']
        );

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
            $baseQuery = PlayerAchievementSet::query()
                ->where('user_id', $playerGame->user_id)
                ->whereIn('achievement_set_id', $activeAchievementSets)
                ->whereHas('achievementSet', function ($query) {
                    $query->whereNotNull('achievements_first_published_at');
                });

            $baseQuery->clone()
                ->whereNull('completed_at')
                ->increment('time_taken', $adjustment);

            if ($playerSession->hardcore || $playerGame->user->points > $playerGame->user->points_softcore) {
                $baseQuery->clone()
                    ->whereNull('completed_hardcore_at')
                    ->increment('time_taken_hardcore', $adjustment);
            }
        }
    }
}
