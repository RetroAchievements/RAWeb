<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Community\Enums\AwardType;
use App\Community\Enums\TicketState;
use App\Community\Enums\UserGameListType;
use App\Models\Game;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserGameListEntry;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\UnlockMode;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;

class PlayerGameActivityService
{
    public array $sessions = [];

    public function initialize(User $user, Game $game): void
    {
        $playerSessions = $user->playerSessions()->where('game_id', $game->id)->get();

        foreach ($playerSessions as $playerSession) {
            $session = [
                'type' => 'player-session',
                'playerSession' => $playerSession,
                'startTime' => $playerSession->created_at,
                'endTime' => $playerSession->created_at->addSeconds($playerSession->duration),
                'duration' => $playerSession->duration,
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
        }

        foreach ($this->sessions as &$session) {
            $this->sortEvents($session['events']);
        }
    }

    private function addUnlockEvent(object $playerAchievement, Carbon $when, bool $hardcore): void
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
            ],
        ];

        $unlocker = null;
        if ($playerAchievement->unlocker_id) {
            $unlocker = User::firstWhere('id', $playerAchievement->unlocker_id);
            if ($unlocker) {
                $event['unlocker'] = $unlocker;
            }
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
        foreach ($this->sessions as &$session)
        {
            if ($session['type'] == 'player-session' &&
                $session['startTime'] <= $when &&
                $session['endTime'] >= $when) {
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
        foreach ($this->sessions as &$session)
        {
            if ($session['type'] == 'generated' &&
                $session['startTime'] >= $whenBefore &&
                $session['endTime'] <= $whenAfter) {

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
        foreach ($this->sessions as &$session)
        {
            if ($session['type'] == $type && 
                $session['startTime'] <= $when &&
                $session['endTime'] >= $when) {
                break;
            }

            $index++;
        }

        return $index;
    }
}
