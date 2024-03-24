<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\Game;
use App\Models\User;
use Carbon\Carbon;

class PlayerGameActivityService
{
    public array $sessions = [];
    public int $achievementsUnlocked = 0;

    public function initialize(User $user, Game $game): void
    {
        $playerSessions = $user->playerSessions()->where('game_id', $game->id)->get();

        foreach ($playerSessions as $playerSession) {
            $session = [
                'type' => 'player-session',
                'playerSession' => $playerSession,
                'startTime' => $playerSession->created_at,
                'endTime' => $playerSession->created_at->addMinutes($playerSession->duration),
                'duration' => $playerSession->duration * 60,
                'events' => [],
            ];

            if (!empty($playerSession->rich_presence)) {
                $event = [
                    'type' => 'rich-presence',
                    'description' => $playerSession->rich_presence,
                    'when' => $playerSession->rich_presence_updated_at,
                ];

                $this->insertEvent($session, $event);
            }

            $this->sessions[] = $session;
        }

        $playerAchievements = $user->playerAchievements()
            ->join('Achievements', 'player_achievements.achievement_id', '=', 'Achievements.ID')
            ->where('Achievements.GameID', '=', $game->id)
            ->orderBy('player_achievements.unlocked_at')
            ->select(['player_achievements.*', 'Achievements.Flags', 'Achievements.Title',
                      'Achievements.Description', 'Achievements.Points', 'Achievements.BadgeName', 'Achievements.type'])
            ->get();
        foreach ($playerAchievements as $playerAchievement) {
            if ($playerAchievement->unlocked_hardcore_at) {
                $this->addUnlockEvent($playerAchievement, $playerAchievement->unlocked_hardcore_at, true);

                if ($playerAchievement->unlocked_hardcore_at != $playerAchievement->unlocked_at) {
                    $this->addUnlockEvent($playerAchievement, $playerAchievement->unlocked_at, false);
                }
            } else {
                $this->addUnlockEvent($playerAchievement, $playerAchievement->unlocked_at, false);
            }

            $this->achievementsUnlocked++;
        }

        foreach ($this->sessions as &$session) {
            $this->sortEvents($session['events']);
        }
    }

    private function addUnlockEvent(object $playerAchievement, Carbon $when, bool $hardcore, bool $repeat = false): void
    {
        $event = [
            'type' => 'unlock',
            'id' => $playerAchievement->achievement_id,
            'hardcore' => $hardcore,
            'when' => $when,
            'achievement' => [ // fields necessary for generating tooltip
                'ID' => $playerAchievement->achievement_id,
                'Title' => $playerAchievement->Title,
                'Description' => $playerAchievement->Description,
                'Points' => $playerAchievement->Points,
                'BadgeName' => $playerAchievement->BadgeName,
                'Flags' => $playerAchievement->Flags,
                'HardcoreMode' => $hardcore,
            ],
        ];

        $unlocker = null;
        if ($playerAchievement->unlocker_id) {
            $unlocker = User::firstWhere('id', $playerAchievement->unlocker_id);
            if ($unlocker) {
                $event['unlocker'] = $unlocker;
            }
        }

        if (!$hardcore && $when < $playerAchievement->unlocked_hardcore_at) {
            $event['hardcoreLater'] = true;
        }

        $existingSessionIndex = $this->findSession('player-session', $when);
        if ($existingSessionIndex < 0) {
            if ($unlocker) {
                $existingSessionIndex = $this->generateSession('manual-unlock', $when);
            } else {
                $existingSessionIndex = $this->generateSession('generated', $when);
            }
        }

        $this->sessions[$existingSessionIndex]['events'][] = $event;
    }

    private function insertEvent(array &$session, array $event): void
    {
        $session['events'][] = $event;
        $this->sortEvents($session['events']);
    }

    private function sortEvents(array &$events): void
    {
        usort($events, function ($a, $b) {
            $diff = $a['when']->timestamp - $b['when']->timestamp;
            if ($diff === 0) {
                if ($a['type'] !== $b['type']) {
                    // rich-presence event should always be after unlocks
                    if ($a['type'] === 'rich-presence') {
                        return 1;
                    } elseif ($b['type'] === 'rich-presence') {
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

    private function findSession(string $type, Carbon $when): int
    {
        $index = 0;
        foreach ($this->sessions as &$session) {
            if ($session['type'] == 'player-session'
                && $session['startTime'] <= $when
                && $session['endTime'] >= $when) {
                return $index;
            }

            $index++;
        }

        return -1;
    }

    private function generateSession(string $type, Carbon $when): int
    {
        $mergeHours = ($type == 'manual-unlock') ? 1 : 4;
        $whenBefore = $when->clone()->subHours($mergeHours);
        $whenAfter = $when->clone()->addHours($mergeHours);

        $index = 0;
        foreach ($this->sessions as &$session) {
            if ($session['type'] == 'generated'
                && $session['startTime'] >= $whenBefore
                && $session['endTime'] <= $whenAfter) {

                if ($when < $session['startTime']) {
                    $session['startTime'] = $when;
                    $session['duration'] = $session['endTime']->diffInSeconds($when);
                } elseif ($when > $session['endTime']) {
                    $session['endTime'] = $when;
                    $session['duration'] = $when->diffInSeconds($session['startTime']);
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

        $this->sessions[] = $newSession;
        usort($this->sessions, fn ($a, $b) => $a['startTime']->timestamp - $b['startTime']->timestamp);

        $index = 0;
        foreach ($this->sessions as &$session) {
            if ($session['type'] == $type
                && $session['startTime'] <= $when
                && $session['endTime'] >= $when) {
                break;
            }

            $index++;
        }

        return $index;
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
            if ($session['type'] === 'manual-unlock') {
                continue;
            } elseif ($session['type'] == 'generated') {
                $generatedSessionCount++;
            }

            $totalTime += $session['duration'];

            $hasAchievements = false;
            foreach ($session['events'] as $event) {
                if ($event['type'] == 'unlock') {
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
                if ($session['type'] == 'generated') {
                    $generatedUnlockSessionCount++;
                }
            } elseif ($session['type'] == 'player-session') {
                $intermediateTime += $session['duration'];
                $intermediateSessionCount++;
            }
        }

        // assume every achievement took roughly the same amount of time to earn. divide the
        // user's total known playtime by the number of achievements they've earned to get the
        // approximate time per achievement earned. add this value to each session to account
        // for time played after getting the last achievement of the session.
        $sessionAdjustment = 0;
        if ($generatedSessionCount > 0 && $achievementsUnlocked > 0) {
            $sessionAdjustment = $achievementsTime / $achievementsUnlocked;

            $totalTime += $sessionAdjustment * $generatedSessionCount;

            if ($generatedUnlockSessionCount > 0) {
                $achievementsTime += $sessionAdjustment * $generatedUnlockSessionCount;
            }
        }

        return [
            // total time from sessions where achievements were earned
            'achievementPlaytime' => $achievementsTime,
            // number of sessions where achievements were earned
            'achievementSessionCount' => $unlockSessionCount,
            // adjustment applied to generated sessions
            'generatedSessionAdjustment' => $sessionAdjustment,
            // distance between the first unlock and last unlock (includes time between sessions)
            'totalUnlockTime' => ($lastAchievementTime != null) ?
                $lastAchievementTime->diffInSeconds($firstAchievementTime) : 0,
            // total time from all sessions (including those before the first or after the last earned achievement)
            'totalPlaytime' => $totalTime,
        ];
    }
}
