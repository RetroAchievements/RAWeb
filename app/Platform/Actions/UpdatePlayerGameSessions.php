<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\ActivityType;
use App\Platform\Enums\AchievementType;
use App\Platform\Events\PlayerGameMetricsUpdated;
use App\Platform\Models\Achievement;
use App\Platform\Models\PlayerAchievement;
use App\Platform\Models\PlayerGame;
use App\Platform\Models\PlayerSession;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class UpdatePlayerGameSessions
{
    public function execute(PlayerGame $playerGame, bool $silent = false): void
    {
        $game = $playerGame->game;
        $user = $playerGame->user;

        if (!$user) {
            return;
        }

        // step one: get sessions from player_sessions and player_achievements
        $sessions = getUserGameActivity($user->User, $game->ID)['Sessions'];

        // step two: merge achievements from Activity
        $addAchievementToSession = function(&$sessions, $row): void {
            $createSessionAchievement = function($row, $when): array {
                return [
                    'When' => $when,
                    'AchievementID' => $row['AchievementID'],
                    'HardcoreMode' => (bool) $row['HardcoreMode'],
                ];
            };

            $mergeSessionAchievement = function(&$session, $row, $when) use ($createSessionAchievement): void {
                foreach ($session['Achievements'] as &$achievement) {
                    if ($achievement['AchievementID'] == $row['AchievementID'] && $achievement['When'] == $when) {
                        if (!$achievement['HardcoreMode']) {
                            $achievement['HardcoreMode'] = (bool) $row['HardcoreMode'];
                        }
                        return;
                    }
                }

                $session['Achievements'][] = $createSessionAchievement($row, $when);
                usort($session['Achievements'], fn ($a, $b) => $a['When'] - $b['When']);
            };

            $maxSessionGap = 4 * 60 * 60; // 4 hours

            $when = strtotime($row['timestamp']);

            $possibleSession = null;
            foreach ($sessions as &$session) {
                if ($session['StartTime'] <= $when) {
                    if ($session['EndTime'] + $maxSessionGap > $when) {
                        $mergeSessionAchievement($session, $row, $when);
                        $session['EndTime'] = $when;
                        return;
                    }
                    $possibleSession = $session;
                }
            }

            if ($possibleSession) {
                if ($when - $possibleSession['EndTime'] < $maxSessionGap) {
                    $mergeSessionAchievement($possibleSession, $row, $when);
                    $possibleSession['EndTime'] = $when;
                    return;
                }

                $index = array_search($sessions, $possibleSession);
                if ($index < count($sessions)) {
                    $possibleSession = $sessions[$index + 1];
                    if ($possibleSession['StartTime'] - $when < $maxSessionGap) {
                        $mergeSessionAchievement($possibleSession, $row, $when);
                        $possibleSession['StartTime'] = $when;
                        return;
                    }
                }
            }

            $sessions[] = [
                'StartTime' => $when,
                'EndTime' => $when,
                'IsGenerated' => true,
                'Achievements' => [$createSessionAchievement($row, $when)],
            ];
            usort($sessions, fn ($a, $b) => $a['StartTime'] - $b['StartTime']);
        };

        $query = "SELECT a.timestamp, a.data AS AchievementID, a.data2 AS HardcoreMode, ach.Flags
                  FROM Activity a
                  INNER JOIN Achievements ach ON ach.ID = a.data
                  WHERE ach.GameID={$game->ID} AND a.User=:user
                  AND a.activitytype=" . ActivityType::UnlockedAchievement;

        foreach (legacyDbFetchAll($query, ['user' => $user->User]) as $row) {
            $addAchievementToSession($sessions, $row);
        }
 
        // step three: merge StartedPlaying from Activity
        $mergeStartTime = function(&$sessions, $when): void {
            $stop = count($sessions);
            for ($index = 0; $index < $stop; $index++) {
                $session = &$sessions[$index];

                if ($session['StartTime'] > $when) {
                    $maxSessionGap = 4 * 60 * 60; // 4 hours
                    if ($session['StartTime'] < $when + $maxSessionGap) {
                        $session['StartTime'] = $when;
                        return;
                    }

                    // not within 4 hours, create new session
                    break;
                }
                if ($session['StartTime'] == $when) {
                    return;
                }
            }

            $sessions[] = [
                'StartTime' => $when,
                'EndTime' => $when,
                'IsGenerated' => true,
                'Achievements' => [],
            ];
            usort($sessions, fn ($a, $b) => $a['StartTime'] - $b['StartTime']);
        };

        $query = "SELECT a.timestamp, a.lastupdate, a.data
                  FROM Activity a
                  WHERE a.User=:user AND a.data={$game->ID}
                  AND a.activitytype=" . ActivityType::StartedPlaying;

        foreach (legacyDbFetchAll($query, ['user' => $user->User]) as $row) {
            $mergeStartTime($sessions, strtotime($row['timestamp']));

            // new session within 12 hours of existing session gets merged into same session.
            // the lastupdate is a new session being started - not the length of the session!
            if ($row['lastupdate'] !== $row['timestamp']) {
                $mergeStartTime($sessions, strtotime($row['lastupdate']));
            }
        }

        // step four: eliminate sessions created after v5 deployment
        $firstNonGeneratedPlayerSessionTimestamp = Carbon::create(2023, 10, 14, 13, 16, 42);

        $index = count($sessions);
        while ($index > 0 && $sessions[--$index]['StartTime'] >= $firstNonGeneratedPlayerSessionTimestamp->unix()) {
            unset($sessions[$index]);
        }

        // step five: merge rich presence from UserAccounts
        if ($user->LastGameID == $game->ID && !empty($user->RichPresenceMsg)) {
            $when = $user->RichPresenceMsgDate->unix();

            $index = count($sessions);
            while ($index) {
                $session = $sessions[--$index];
                if ($session['EndTime'] < $when) {
                    $maxSessionGap = 4 * 60 * 60; // 4 hours
                    if ($session['EndTime'] + $maxSessionGap > $when ) {
                        $session['EndTime'] = $when;
                        $session['RichPresence'] = $user->RichPresenceMsg;
                    }
                    break;
                }
            }
        }

        // step six: flush player_sessions
        $playerSessions = PlayerSession::where('user_id', '=', $user->id)
            ->where('game_id', '=', $game->id)
            ->where('created_at', '<', $firstNonGeneratedPlayerSessionTimestamp);
        $index = 0;
        foreach ($playerSessions->get() as $playerSession) {
            $session = $sessions[$index++];
            $playerSession->created_at = $session['StartTime'];
            $playerSession->updated_at = $session['EndTime'];
            $playerSession->duration = (max(($session['EndTime'] - $session['StartTime']) / 60, 1));
            $playerSession->save();
        }

        while ($index < count($sessions)) {
            $session = $sessions[$index++];

            $playerSessionAttrs = [
                'user_id' => $user->id,
                'game_id' => $game->id,
                'created_at' => $session['StartTime'],
                'updated_at' => $session['EndTime'],
                'duration' => (max(($session['EndTime'] - $session['StartTime']) / 60, 1)),
            ];

            $hasHardcore = false;
            $hasSoftcore = false;
            if (in_array('Achievements', $session)) {
                foreach ($session['Achievements'] as $achievement) {
                    if ($achievement['HardcoreMode']) {
                        $hasHardcore = true;
                    } else {
                        $hasSoftcore = true;
                    }
                }
            }
            if ($hasHardcore && !$hasSoftcore) {
                $playerSessionAttrs['hardcore'] = 1;
            } else if ($hasSoftcore && !$hasHardcore) {
                $playerSessionAttrs['hardcore'] = 0;
            }

            if (array_key_exists('RichPresence', $session)) {
                $playerSessionAttrs['rich_presence'] = $session['RichPresence'];
                $playerSessionAttrs['rich_presence_updated_at'] = $user->RichPresenceMsgDate;
            }

            $user->playerSessions()->save(new PlayerSession($playerSessionAttrs));
        }
    }
}
