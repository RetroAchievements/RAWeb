<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Models\Game;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CompareUnlocksController extends Controller
{
    public function __invoke(Request $request, Game $game, User $user): View
    {
        $activeUser = $request->user();
        if ($activeUser === null) {
            abort(403);
        }

        $validatedData = $request->validate([
            'sort' => 'sometimes|string|in:selfUnlocks,otherUnlocks,display,title',
        ]);
        $sortOrder = $validatedData['sort'] ?? 'display';

        $achievements = [];
        foreach ($game->achievements()->published()->get() as $achievement) {
            $achievements[$achievement->ID] = $achievement->toArray();
        }

        $activeUserUnlocks = $activeUser->playerAchievements()
            ->join('Achievements', 'Achievements.ID', '=', 'player_achievements.achievement_id')
            ->where('Achievements.GameID', $game->ID)
            ->select(['achievement_id', 'unlocked_at', 'unlocked_hardcore_at']);
        foreach ($activeUserUnlocks->get() as $unlock) {
            if (!array_key_exists($unlock->achievement_id, $achievements)) {
                continue;
            }

            if ($unlock->unlocked_hardcore_at) {
                $achievements[$unlock->achievement_id]['userTimestampRaw'] = $unlock->unlocked_hardcore_at;
                $achievements[$unlock->achievement_id]['userTimestamp'] = Carbon::parse($unlock->unlocked_hardcore_at)->format('d M Y, g:ia');
                $achievements[$unlock->achievement_id]['userHardcore'] = true;
            } elseif ($unlock->unlocked_at) {
                $achievements[$unlock->achievement_id]['userTimestampRaw'] = $unlock->unlocked_at;
                $achievements[$unlock->achievement_id]['userTimestamp'] = Carbon::parse($unlock->unlocked_at)->format('d M Y, g:ia');
                $achievements[$unlock->achievement_id]['userHardcore'] = false;
            }
        }

        $otherUserUnlocks = $user->playerAchievements()
            ->join('Achievements', 'Achievements.ID', '=', 'player_achievements.achievement_id')
            ->where('Achievements.GameID', $game->ID)
            ->select(['achievement_id', 'unlocked_at', 'unlocked_hardcore_at']);
        foreach ($otherUserUnlocks->get() as $unlock) {
            if (!array_key_exists($unlock->achievement_id, $achievements)) {
                continue;
            }

            if ($unlock->unlocked_hardcore_at) {
                $achievements[$unlock->achievement_id]['otherUserTimestampRaw'] = $unlock->unlocked_hardcore_at;
                $achievements[$unlock->achievement_id]['otherUserTimestamp'] = Carbon::parse($unlock->unlocked_hardcore_at)->format('d M Y, g:ia');
                $achievements[$unlock->achievement_id]['otherUserHardcore'] = true;
            } elseif ($unlock->unlocked_at) {
                $achievements[$unlock->achievement_id]['otherUserTimestampRaw'] = $unlock->unlocked_at;
                $achievements[$unlock->achievement_id]['otherUserTimestamp'] = Carbon::parse($unlock->unlocked_at)->format('d M Y, g:ia');
                $achievements[$unlock->achievement_id]['otherUserHardcore'] = false;
            }
        }

        $this->sortList($achievements, $sortOrder);

        return view('pages.game.[game].achievements.compare.[user]', [
            'user' => $activeUser,
            'otherUser' => $user,
            'game' => $game,
            'achievements' => $achievements,
            'sortOrder' => $sortOrder,
        ]);
    }

    private function sortList(array &$achievements, string $sortOrder): void
    {
        $sortFunction = match ($sortOrder) {
            default => function ($a, $b) {
                return $this->sortByUnlockTimestamps($a, $b, 'userTimestampRaw');
            },
            'otherUnlocks' => function ($a, $b) {
                return $this->sortByUnlockTimestamps($a, $b, 'otherUserTimestampRaw');
            },
            'display' => function ($a, $b) {
                return $a['DisplayOrder'] <=> $b['DisplayOrder'];
            },
            'title' => function ($a, $b) {
                return $a['Title'] <=> $b['Title'];
            },
        };

        usort($achievements, $sortFunction);
    }

    private function sortByUnlockTimestamps(array $a, array $b, string $field): int
    {
        $aTimestamp = $a[$field] ?? null;
        $bTimestamp = $b[$field] ?? null;
        if ($aTimestamp != $bTimestamp) {
            if ($aTimestamp === null) {
                return 1;
            } elseif ($bTimestamp == null) {
                return -1;
            } else {
                return $aTimestamp <=> $bTimestamp;
            }
        }

        return $a['DisplayOrder'] <=> $b['DisplayOrder'];
    }
}
