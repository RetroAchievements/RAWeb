<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Enums\PlayerGameActivityEventType;
use App\Enums\PlayerGameActivitySessionType;
use App\Models\Achievement;
use App\Models\AchievementSet;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\PlayerGame;
use App\Models\PlayerProgressReset;
use App\Models\User;
use App\Platform\Actions\ComputeAchievementsSetPublishedAtAction;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Enums\PlayerProgressResetType;
use App\Platform\Enums\UnlockMode;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class PlayerGameActivityService
{
    public array $sessions = [];
    public int $achievementsUnlocked = 0;
    private int $sessionAdjustment = 0;

    /** @var Collection<int, PlayerGame> */
    private Collection $playerGames;

    public function initialize(User $user, Game $game, bool $withSubsets = false): void
    {
        $query = GameAchievementSet::where('game_id', $game->id)
            ->with(['achievementSet.achievements' => fn ($q) => $q->where('is_promoted', true)
                ->select(['achievements.id', 'type', 'points', 'points_weighted']),
            ]);

        if (!$withSubsets) {
            $query->where('type', AchievementSetType::Core);
        }
        $gameAchievementSets = $query->get();

        $achievementAchievementSets = [];
        $achievementSetIds = [];
        foreach ($gameAchievementSets as $gameAchievementSet) {
            foreach ($gameAchievementSet->achievementSet->achievements as $achievement) {
                $achievementAchievementSets[$achievement->id] = $gameAchievementSet->achievementSet->id;
            }

            $achievementSetIds[] = $gameAchievementSet->achievementSet->id;
        }
        $achievementIds = array_keys($achievementAchievementSets);

        if ($withSubsets) {
            $coreGameAchievementSetIds = [];
            $coreGameAchievementSets = GameAchievementSet::query()
                ->whereIn('achievement_set_id', $achievementSetIds)
                ->where('type', AchievementSetType::Core)
                ->get();
            foreach ($coreGameAchievementSets as $coreGameAchievementSet) {
                $coreGameAchievementSetIds[$coreGameAchievementSet->game_id] = $coreGameAchievementSet->achievement_set_id;
            }
            $gameIds = array_keys($coreGameAchievementSetIds);
            $achievementIds = array_unique($achievementIds);
        } else {
            $coreGameAchievementSetIds = [$game->id => $gameAchievementSets->first()->achievement_set_id];
            $gameIds[] = $game->id;
        }

        // if this game appears as a bonus/specialty subset, pull in the sessions for the
        // parent game(s) as well in case the user is playing through multiset.
        $isSubset = false;
        $parentGameAchievementSets = GameAchievementSet::query()
            ->where('achievement_set_id', $gameAchievementSets->first()->achievement_set_id)
            ->whereIn('type', [AchievementSetType::Bonus, AchievementSetType::Specialty])
            ->get();
        foreach ($parentGameAchievementSets as $parentGameAchievementSet) {
            $isSubset = true;

            $parentGameId = $parentGameAchievementSet->game_id;
            $gameIds[] = $parentGameId;

            $coreGameAchievementSetIds[$parentGameId] = GameAchievementSet::query()
                ->where('game_id', $parentGameId)
                ->where('type', AchievementSetType::Core)
                ->value('achievement_set_id');
        }

        $playerSessionsQuery = $user->playerSessions()
            ->with('gameHash')
            ->whereIn('game_id', $gameIds);

        $playerSessions = $playerSessionsQuery->orderBy('created_at')->get();

        foreach ($playerSessions as $playerSession) {
            $session = [
                'type' => PlayerGameActivitySessionType::Player,
                'playerSession' => $playerSession,
                'startTime' => $playerSession->created_at,
                'endTime' => $playerSession->created_at->addMinutes($playerSession->duration),
                'duration' => $playerSession->duration * 60,
                'userAgent' => $playerSession->user_agent,
                'events' => [],
            ];

            $session['achievementSetId'] = $coreGameAchievementSetIds[$playerSession->game_id];

            if (!empty($playerSession->rich_presence)) {
                $session['events'][] = [
                    'type' => PlayerGameActivityEventType::RichPresence,
                    'description' => $playerSession->rich_presence,
                    'when' => $playerSession->rich_presence_updated_at,
                ];

                // since $playerSession->duration is in minutes, and $playerSession->rich_presence_updated_at
                // is an actual timestamp, it might be some number of seconds ahead of 'endTime' due to duration
                // being floored by the conversion to minutes.
                if ($playerSession->rich_presence_updated_at > $session['endTime']) {
                    $session['endTime'] = $playerSession->rich_presence_updated_at;
                    $session['duration'] = (int) $session['endTime']->diffInSeconds($session['startTime'], true);
                }
            }

            $this->sessions[] = $session;
        }

        // player_games records have more granular end times. try to merge them in
        $this->playerGames = PlayerGame::where('user_id', $user->id)->whereIn('game_id', $gameIds)->get();
        foreach ($this->playerGames as &$playerGame) {
            // store the achievement_set_id on the playerGame for later use
            $playerGame->achievement_set_id = $coreGameAchievementSetIds[$playerGame->game_id];

            if ($playerGame->last_played_at) {
                $whenBefore = $playerGame->last_played_at->clone()->subMinutes(5);
                $whenAfter = $playerGame->last_played_at->clone()->addMinutes(5);

                foreach ($this->sessions as &$session) {
                    if ($session['endTime'] >= $whenBefore && $session['endTime'] <= $whenAfter) {
                        $session['endTime'] = $playerGame->last_played_at;
                        break;
                    }
                }

                if ($playerGame->game_id === $game->id && $isSubset) {
                    // remove any parent sessions outside of the subset playtime range
                    $this->sessions = array_values(array_filter($this->sessions, function ($session) use ($playerGame) {
                        return $session['startTime'] <= $playerGame->last_played_at
                            && $session['endTime'] >= $playerGame->created_at;
                    }));
                }
            }
        }

        $playerAchievementsQuery = $user->playerAchievements()
            ->join('achievements', 'player_achievements.achievement_id', '=', 'achievements.id')
            ->whereIn('achievements.id', $achievementIds)
            ->orderBy('player_achievements.unlocked_at')
            ->select([
                'player_achievements.*',
                'achievements.is_promoted',
                'achievements.title',
                'achievements.description',
                'achievements.points',
                'achievements.points_weighted',
                'achievements.image_name',
                'achievements.type',
            ]);

        $playerAchievements = $playerAchievementsQuery->get();

        // Pre-load all unlockers to avoid N+1 queries.
        $unlockerIds = $playerAchievements->pluck('unlocker_id')->filter()->unique();
        $unlockers = [];
        if ($unlockerIds->isNotEmpty()) {
            $unlockers = User::whereIn('id', $unlockerIds)->get()->keyBy('id');
        }

        foreach ($playerAchievements as $playerAchievement) {
            // Pass the pre-loaded unlocker.
            $unlocker = $unlockers[$playerAchievement->unlocker_id] ?? null;

            $achievementSetId = $achievementAchievementSets[$playerAchievement->achievement_id] ?? $coreGameAchievementSetIds[$game->id];
            if ($playerAchievement->unlocked_hardcore_at) {
                $this->addUnlockEvent(
                    $playerAchievement,
                    $playerAchievement->unlocked_hardcore_at,
                    $achievementSetId,
                    true,
                    $unlocker
                );

                if ($playerAchievement->unlocked_hardcore_at != $playerAchievement->unlocked_at) {
                    $this->addUnlockEvent(
                        $playerAchievement,
                        $playerAchievement->unlocked_at,
                        $achievementSetId,
                        false,
                        $unlocker
                    );
                }
            } else {
                $this->addUnlockEvent(
                    $playerAchievement,
                    $playerAchievement->unlocked_at,
                    $achievementSetId,
                    false,
                    $unlocker
                );
            }

            $this->achievementsUnlocked++;
        }

        // TODO: process claims in another queue

        foreach ($this->sessions as &$session) {
            $this->sortEvents($session['events']);
        }
    }

    private function addUnlockEvent(object $playerAchievement, Carbon $when, int $achievementSetId, bool $hardcore, ?User $unlocker = null): void
    {
        $event = [
            'type' => PlayerGameActivityEventType::Unlock,
            'id' => $playerAchievement->achievement_id,
            'hardcore' => $hardcore,
            'when' => $when,
            'achievement' => [ // fields necessary for generating tooltip
                'ID' => $playerAchievement->achievement_id,
                'Title' => $playerAchievement->title,
                'Description' => $playerAchievement->description,
                'Points' => $playerAchievement->points,
                'TrueRatio' => $playerAchievement->points_weighted,
                'BadgeName' => $playerAchievement->image_name,
                'Flags' => $playerAchievement->is_promoted ? Achievement::FLAG_PROMOTED : Achievement::FLAG_UNPROMOTED,
                'HardcoreMode' => $hardcore,
            ],
        ];

        if ($unlocker) {
            $event['unlocker'] = $unlocker;
        }

        if (!$hardcore && $when < $playerAchievement->unlocked_hardcore_at) {
            $event['hardcoreLater'] = true;
        }

        $existingSessionIndex = $this->findSession(PlayerGameActivitySessionType::Player, $when);
        if ($existingSessionIndex < 0) {
            if ($unlocker) {
                $existingSessionIndex = $this->generateSession(PlayerGameActivitySessionType::ManualUnlock, $when, $achievementSetId);
            } else {
                $existingSessionIndex = $this->generateSession(PlayerGameActivitySessionType::Reconstructed, $when, $achievementSetId);
            }
        }

        $this->sessions[$existingSessionIndex]['events'][] = $event;
    }

    public function addCustomEvent(
        Carbon $when,
        PlayerGameActivitySessionType $sessionType,
        string $description,
        string $header = '',
    ): void {
        $event = [
            'type' => PlayerGameActivityEventType::Custom,
            'header' => $header,
            'description' => $description,
            'when' => $when,
        ];

        $existingSessionIndex = $this->findSession(PlayerGameActivitySessionType::Player, $when);
        if ($existingSessionIndex < 0) {
            $existingSessionIndex = $this->generateSession($sessionType, $when);
        }

        $this->sessions[$existingSessionIndex]['events'][] = $event;
        $this->sortEvents($this->sessions[$existingSessionIndex]['events']);
    }

    private function sortEvents(array &$events): void
    {
        usort($events, function ($a, $b) {
            $diff = $a['when']->timestamp - $b['when']->timestamp;
            if ($diff === 0) {
                if ($a['type'] !== $b['type']) {
                    // rich-presence event should always be after unlocks
                    if ($a['type'] === PlayerGameActivityEventType::RichPresence) {
                        return 1;
                    } elseif ($b['type'] === PlayerGameActivityEventType::RichPresence) {
                        return -1;
                    }
                } else {
                    // two events at same time should be sub-sorted by ID
                    $diff = ($a['ID'] ?? 0) - ($b['ID'] ?? 0);
                }
            }

            return $diff;
        });
    }

    private function findSession(PlayerGameActivitySessionType $type, Carbon $when): int
    {
        $index = 0;
        foreach ($this->sessions as &$session) {
            if ($session['type'] === $type
                && $session['startTime'] <= $when
                && $session['endTime'] >= $when) {
                return $index;
            }

            $index++;
        }

        return -1;
    }

    private function generateSession(PlayerGameActivitySessionType $type, Carbon $when, ?int $achievementSetId = null): int
    {
        $mergeHours = ($type === PlayerGameActivitySessionType::ManualUnlock) ? 1 : 4;
        $whenBefore = $when->clone()->subHours($mergeHours);
        $whenAfter = $when->clone()->addHours($mergeHours);

        $index = 0;
        foreach ($this->sessions as &$session) {
            if ($session['type'] === PlayerGameActivitySessionType::Reconstructed
                && $session['startTime'] >= $whenBefore
                && $session['endTime'] <= $whenAfter
                && ($achievementSetId === 0 || ($session['achievementSetId'] ?? 0) === $achievementSetId)) {

                if ($when < $session['startTime']) {
                    $session['startTime'] = $when;
                    $session['duration'] = (int) $session['endTime']->diffInSeconds($when, true);
                } elseif ($when > $session['endTime']) {
                    $session['endTime'] = $when;
                    $session['duration'] = (int) $when->diffInSeconds($session['startTime'], true);
                }

                return $index;
            }

            $index++;
        }

        $newSession = [
            'type' => $type,
            'startTime' => $when,
            'endTime' => $when,
            'duration' => 0,
            'events' => [],
        ];

        if ($achievementSetId > 0) {
            $newSession['achievementSetId'] = $achievementSetId;
        }

        $this->sessions[] = $newSession;
        usort($this->sessions, fn ($a, $b) => $a['startTime']->timestamp - $b['startTime']->timestamp);

        return $this->findSession($type, $when);
    }

    public function summarize(): array
    {
        $generatedSessionCount = 0;
        $generatedUnlockSessionCount = 0;
        $totalTime = 0;
        $achievementsUnlocked = 0;
        $achievementsTime = 0;
        $unlockSessionCount = 0;
        $intermediateTime = 0;
        $intermediateSessionCount = 0;
        $firstAchievementTime = null;
        $lastAchievementTime = null;

        foreach ($this->sessions as $session) {
            if ($session['type'] === PlayerGameActivitySessionType::ManualUnlock) {
                continue;
            } elseif ($session['type'] === PlayerGameActivitySessionType::Reconstructed) {
                $generatedSessionCount++;
            }

            $totalTime += $session['duration'];

            $hasAchievements = false;
            foreach ($session['events'] as $event) {
                if ($event['type'] === PlayerGameActivityEventType::Unlock) {
                    $achievementsUnlocked++;
                    $hasAchievements = true;

                    if ($firstAchievementTime === null || $event['when'] < $firstAchievementTime) {
                        $firstAchievementTime = $event['when'];
                    }
                    if ($lastAchievementTime === null || $event['when'] > $lastAchievementTime) {
                        $lastAchievementTime = $event['when'];
                    }
                }
            }

            if ($hasAchievements) {
                if ($achievementsTime > 0) {
                    $achievementsTime += $intermediateTime;
                    $unlockSessionCount += $intermediateSessionCount;
                }
                $achievementsTime += $session['duration'];
                $intermediateTime = 0;
                $intermediateSessionCount = 0;

                $unlockSessionCount++;
                if ($session['type'] === PlayerGameActivitySessionType::Reconstructed) {
                    $generatedUnlockSessionCount++;
                }
            } elseif ($session['type'] === PlayerGameActivitySessionType::Player) {
                $intermediateTime += $session['duration'];
                $intermediateSessionCount++;
            }
        }

        // assume every achievement took roughly the same amount of time to earn. divide the
        // user's total known playtime by the number of achievements they've earned to get the
        // approximate time per achievement earned. add this value to each session to account
        // for time played after getting the last achievement of the session.
        $this->sessionAdjustment = 0;
        if ($generatedSessionCount > 0 && $achievementsUnlocked > 0) {
            $this->sessionAdjustment = (int) ($achievementsTime / $achievementsUnlocked);

            $totalTime += $this->sessionAdjustment * $generatedSessionCount;

            if ($generatedUnlockSessionCount > 0) {
                $achievementsTime += $this->sessionAdjustment * $generatedUnlockSessionCount;
            }
        }

        return [
            // total time from sessions where achievements were earned
            'achievementPlaytime' => $achievementsTime,
            // number of sessions where achievements were earned
            'achievementSessionCount' => $unlockSessionCount,
            // adjustment applied to generated sessions
            'generatedSessionAdjustment' => $this->sessionAdjustment,
            // distance between the first unlock and last unlock (includes time between sessions)
            'totalUnlockTime' => ($lastAchievementTime != null) ?
                (int) $lastAchievementTime->diffInSeconds($firstAchievementTime, true) : 0,
            // total time from all sessions (including those before the first or after the last earned achievement)
            'totalPlaytime' => $totalTime,
        ];
    }

    public function lastPlayedAt(): ?Carbon
    {
        $lastSession = null;
        foreach ($this->sessions as $session) {
            switch ($session['type']) {
                case PlayerGameActivitySessionType::Player:
                case PlayerGameActivitySessionType::Reconstructed:
                    $lastSession = $session;
                    break;
            }
        }

        return $lastSession ? $lastSession['endTime'] : null;
    }

    public function getBeatProgressMetrics(AchievementSet $achievementSet, PlayerGame $playerGame): array
    {
        $startTime = $this->getAchievementEarningStartTime($achievementSet);

        return [
            'beatPlaytimeSoftcore' => $playerGame->beaten_at ? $this->calculatePlaytime($startTime, $playerGame->beaten_at, UnlockMode::Softcore) : null,
            'beatPlaytimeHardcore' => $playerGame->beaten_hardcore_at ? $this->calculatePlaytime($startTime, $playerGame->beaten_hardcore_at, UnlockMode::Hardcore) : null,
        ];
    }

    public function getAchievementSetMetrics(AchievementSet $achievementSet): array
    {
        $metrics = [
            'firstUnlockTimeSoftcore' => null,
            'firstUnlockTimeHardcore' => null,
            'lastUnlockTimeSoftcore' => null,
            'lastUnlockTimeHardcore' => null,
        ];

        if ($achievementSet->achievements_published === 0) {
            $metrics['achievementPlaytimeSoftcore'] = 0;
            $metrics['achievementPlaytimeHardcore'] = 0;

            // assume entire playtime has been development work
            $summary = $this->summarize();
            $metrics['devTime'] = $summary['totalPlaytime'];

            return $metrics;
        }

        $this->getFilteredAchievementSetMetrics($achievementSet, $metrics,
            [PlayerGameActivitySessionType::Player, PlayerGameActivitySessionType::Reconstructed]);

        // if no player-initiated unlocks were found, check for manual unlocks
        if (!$metrics['firstUnlockTimeSoftcore'] || !$metrics['firstUnlockTimeHardcore']) {
            $this->getFilteredAchievementSetMetrics($achievementSet, $metrics,
                [PlayerGameActivitySessionType::ManualUnlock]);
        }

        $startTime = $this->getAchievementEarningStartTime($achievementSet, includeSessionAdjustment: true);

        if ($startTime) {
            $playerGame = $this->playerGames->where('achievement_set_id', $achievementSet->id)->first();

            // if the user has completed the game, stop tracking at the last achievement earned
            $endTimeSoftcore = ($playerGame && !$playerGame->completed_at) ? null : $metrics['lastUnlockTimeSoftcore'];
            $endTimeHardcore = ($playerGame && !$playerGame->completed_hardcore_at) ? null : $metrics['lastUnlockTimeHardcore'];

            $metrics['achievementPlaytimeSoftcore'] = $this->calculatePlaytime($startTime, $endTimeSoftcore, UnlockMode::Softcore);
            $metrics['achievementPlaytimeHardcore'] = $this->calculatePlaytime($startTime, $endTimeHardcore, UnlockMode::Hardcore);
        } else {
            // don't count any playtime if achievements haven't been published yet
            $metrics['achievementPlaytimeSoftcore'] = 0;
            $metrics['achievementPlaytimeHardcore'] = 0;
        }

        $metrics['devTime'] = $this->calculatePlaytime(null, $achievementSet->achievements_first_published_at, UnlockMode::Softcore);

        return $metrics;
    }

    private function getFilteredAchievementSetMetrics(AchievementSet $achievementSet, array &$metrics, array $sessionTypes): void
    {
        foreach ($this->sessions as $session) {
            if (!in_array($session['type'], $sessionTypes)) {
                continue;
            }

            if (in_array('achievementSetId', $session)
                && $session['achievementSetId'] != $achievementSet->id) {
                continue;
            }

            foreach ($session['events'] as $event) {
                if ($event['type'] !== PlayerGameActivityEventType::Unlock) {
                    continue;
                }

                if (!$achievementSet->achievements->contains('id', $event['id'])) {
                    // achievement not part of set, ignore
                    continue;
                }

                if ($event['hardcore']) {
                    if (!$metrics['firstUnlockTimeHardcore']) {
                        $metrics['firstUnlockTimeHardcore'] = $event['when'];
                    }
                    $metrics['lastUnlockTimeHardcore'] = $event['when'];
                } else {
                    if (!$metrics['firstUnlockTimeSoftcore']) {
                        $metrics['firstUnlockTimeSoftcore'] = $event['when'];
                    }
                    $metrics['lastUnlockTimeSoftcore'] = $event['when'];
                }
            }
        }

        $metrics['firstUnlockTimeSoftcore'] ??= $metrics['firstUnlockTimeHardcore'];
        $metrics['lastUnlockTimeSoftcore'] ??= $metrics['lastUnlockTimeHardcore'];
    }

    /**
     * Determines the effective start time for calculating achievement-related playtime.
     * Takes into account the achievements published date, any reset dates, and
     * optionally the PlayerGame creation time.
     */
    private function getAchievementEarningStartTime(
        AchievementSet $achievementSet,
        bool $includeSessionAdjustment = false,
    ): ?Carbon {
        if (!$achievementSet->achievements_first_published_at) {
            $achievementSet->achievements_first_published_at = (new ComputeAchievementsSetPublishedAtAction())->execute($achievementSet);
            $achievementSet->save();
        }

        $achievementsPublishedAt = $achievementSet->achievements_first_published_at;
        if (!$achievementsPublishedAt) {
            return null;
        }

        $startTime = $achievementsPublishedAt;
        $playerGame = $this->playerGames->where('achievement_set_id', $achievementSet->id)->first();

        if ($playerGame) {
            // check for account-level or game-specific resets that should override the start time
            $gameReset = PlayerProgressReset::where('user_id', $playerGame->user_id)
                ->where(function ($query) use ($playerGame) {
                    $query->where('type', PlayerProgressResetType::Account)
                        ->orWhere(fn ($q) => $q
                            ->where('type', PlayerProgressResetType::Game)
                            ->where('type_id', $playerGame->game_id)
                        );
                })
                ->orderByDesc('created_at')
                ->first();

            if ($gameReset?->created_at?->gt($startTime)) {
                $startTime = $gameReset->created_at;
            }

            // if the playerGame record was created after achievements were published, use that as the start time.
            if ($includeSessionAdjustment && $playerGame->created_at > $startTime) {
                foreach ($this->sessions as $session) {
                    if ($session['endTime'] > $playerGame->created_at) {
                        // include the whole session containing the playerGame record - if these differ
                        // it's typically because a subset playerGame record didn't get created until
                        // the first achievement was unlocked.
                        $startTime = min($playerGame->created_at, $session['startTime']);
                        break;
                    }
                }
            }
        }

        return $startTime;
    }

    private function calculatePlaytime(?Carbon $startTime, ?Carbon $endTime, int $unlockMode): ?int
    {
        $totalTime = null;

        foreach ($this->sessions as $session) {
            if ($session['type'] === PlayerGameActivitySessionType::ManualUnlock) {
                continue;
            }

            if ($startTime && $startTime->gt($session['endTime'])) {
                // before scan period
                continue;
            }

            if ($endTime && $endTime->lt($session['startTime'])) {
                // after scan period
                break;
            }

            $sessionStartTime = ($startTime && $startTime->gt($session['startTime']))
                ? $startTime : $session['startTime'];
            $sessionEndTime = ($endTime && $endTime->lt($session['endTime']))
                ? $endTime : $session['endTime'];

            $keepSession = false;
            $hasUnlocks = false;
            $lastHardcore = false;
            $firstNonHardcore = null;
            foreach ($session['events'] as $event) {
                if ($event['type'] === PlayerGameActivityEventType::Unlock) {
                    $hasUnlocks = true;
                    if ($event['when']->lt($sessionStartTime) || $event['when']->gt($sessionEndTime)) {
                        // outside scan period
                        continue;
                    }

                    if ($event['hardcore']) {
                        $keepSession = true; // hardcore event also implies softcore

                        if ($unlockMode === UnlockMode::Hardcore) {
                            $lastHardcore = true;
                            $firstNonHardcore = null;
                        }
                    } else {
                        if ($unlockMode === UnlockMode::Softcore) {
                            $keepSession = true;
                            break;
                        }

                        if ($lastHardcore) {
                            $firstNonHardcore = $event['when'];
                            $lastHardcore = false;
                        }
                    }
                }
            }
            if (!$keepSession && $hasUnlocks) {
                continue;
            }
            if ($unlockMode === UnlockMode::Hardcore && $firstNonHardcore) {
                $sessionEndTime = $firstNonHardcore;
            }

            $totalTime += $sessionStartTime->diffInSeconds($sessionEndTime, true);

            if ($session['type'] === PlayerGameActivitySessionType::Reconstructed) {
                $totalTime += $this->sessionAdjustment;
            }
        }

        if ($totalTime > 0) {
            return (int) $totalTime;
        }

        return null;
    }

    // returns array of ['agents' => [], 'duration' => 0, 'durationPercentage' => 0.0]
    public function getClientBreakdown(UserAgentService $userAgentService): array
    {
        $clients = [];

        foreach ($this->sessions as $session) {
            if ($session['userAgent'] ?? null) {
                $userAgent = $session['userAgent'];

                $decoded = $userAgentService->decode($userAgent);
                $client = $decoded['client'];
                if ($decoded['clientVersion'] !== 'Unknown') {
                    $client .= ' (' . $decoded['clientVersion'] . ')';
                }
                if (array_key_exists('clientVariation', $decoded)) {
                    $client .= ' - ' . $decoded['clientVariation'];
                }

                if (array_key_exists($client, $clients)) {
                    $clients[$client]['duration'] = $clients[$client]['duration'] + $session['duration'];
                    if (!in_array($userAgent, $clients[$client]['agents'])) {
                        $clients[$client]['agents'][] = $userAgent;
                    }
                } else {
                    $clients[$client] = [
                        'agents' => [$userAgent],
                        'duration' => $session['duration'],
                    ];
                }
            }
        }

        $totalDuration = 0;
        foreach ($clients as $client) {
            $totalDuration += $client['duration'];
        }

        foreach ($clients as &$client) {
            $client['durationPercentage'] = ($totalDuration > 0) ? round($client['duration'] * 100 / $totalDuration, 1) : 0.0;
        }

        return $clients;
    }
}
