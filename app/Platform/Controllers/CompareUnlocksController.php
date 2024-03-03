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
    public function __invoke(Request $request, User $user, Game $game): View
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

        $this->mergeUserUnlocks($achievements, $activeUser, 'user');
        $this->mergeUserUnlocks($achievements, $user, 'otherUser');

        $this->sortList($achievements, $sortOrder);

        return view('pages.user.[user].game.[game].compare', [
            'user' => $activeUser,
            'otherUser' => $user,
            'game' => $game,
            'achievements' => $achievements,
            'sortOrder' => $sortOrder,
        ]);
    }

    private function mergeUserUnlocks(array &$achievements, User $user, string $prefix): void
    {
        $userUnlocks = $user->playerAchievements()
            ->whereIn('achievement_id', array_keys($achievements))
            ->select(['achievement_id', 'unlocked_at', 'unlocked_hardcore_at']);

        foreach ($userUnlocks->get() as $unlock) {
            if ($unlock->unlocked_hardcore_at) {
                $achievements[$unlock->achievement_id][$prefix . 'TimestampRaw'] = $unlock->unlocked_hardcore_at;
                $achievements[$unlock->achievement_id][$prefix . 'Timestamp'] = Carbon::parse($unlock->unlocked_hardcore_at)->format('d M Y, g:ia');
                $achievements[$unlock->achievement_id][$prefix . 'Hardcore'] = true;
            } elseif ($unlock->unlocked_at) {
                $achievements[$unlock->achievement_id][$prefix . 'TimestampRaw'] = $unlock->unlocked_at;
                $achievements[$unlock->achievement_id][$prefix . 'Timestamp'] = Carbon::parse($unlock->unlocked_at)->format('d M Y, g:ia');
                $achievements[$unlock->achievement_id][$prefix . 'Hardcore'] = false;
            }
        }
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
