<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\AwardType;
use App\Data\PaginatedData;
use App\Data\UserData;
use App\Http\Controller;
use App\Models\Game;
use App\Platform\Data\GameData;
use App\Platform\Data\GameTopAchieversPagePropsData;
use App\Platform\Data\PlayerBadgeData;
use App\Platform\Data\RankedGameTopAchieverData;
use App\Platform\Services\GameTopAchieversService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class GameTopAchieversController extends Controller
{
    public function index(
        Game $game,
        GameTopAchieversService $topAchieversService,
    ): InertiaResponse|RedirectResponse {
        $this->authorize('viewAny', [$game]);

        $perPage = 50;
        $currentPage = (int) request()->input('page', 1);

        // Get total entries to calculate the last page.
        $totalEntries = $game->players_hardcore;
        $lastPage = (int) ceil($totalEntries / $perPage);

        // If the current page exceeds the last page, redirect to the last page.
        if ($currentPage !== 1 && $currentPage > $lastPage) {
            return redirect()->route('game.top-achievers.index', [
                'game' => $game->id,
                'page' => $lastPage,
            ]);
        }

        $topAchieversService->initialize($game);
        $paginatedUsers = $topAchieversService->highestPointEarnersQuery()->paginate($perPage);

        $items = [];
        $rank = 0;
        $rankScore = -1;
        $firstRank = 0;
        $nextRank = 0;
        foreach ($paginatedUsers->items() as $playerGame) {
            $score = $topAchieversService->getPoints($playerGame);

            if ($rank === 0) {
                $rank = $firstRank = $nextRank =
                    ($currentPage === 1) ? 1 : $topAchieversService->getRank($playerGame);
                $rankScore = $score;
            } else {
                $nextRank++;
                if ($score !== $rankScore) {
                    if ($rank === $firstRank && $currentPage !== 1) {
                        $nextRank = $topAchieversService->getRank($playerGame);
                    }
                    $rank = $nextRank;
                    $rankScore = $score;
                }
            }

            $badge = null;
            if ($playerGame->completed_hardcore_at) {
                $badge = new PlayerBadgeData(
                    awardType: AwardType::Mastery,
                    awardData: $game->id,
                    awardDataExtra: 1,
                    awardDate: $playerGame->completed_hardcore_at,
                );
            } elseif ($playerGame->beaten_hardcore_at) {
                $badge = new PlayerBadgeData(
                    awardType: AwardType::GameBeaten,
                    awardData: $game->id,
                    awardDataExtra: 1,
                    awardDate: $playerGame->beaten_hardcore_at,
                );
            }

            $items[] = new RankedGameTopAchieverData(
                rank: $rank,
                user: UserData::fromUser($playerGame->user),
                score: $score,
                badge: $badge,
            );
        }

        $props = new GameTopAchieversPagePropsData(
            GameData::fromGame($game)->include('badgeUrl'),
            PaginatedData::fromLengthAwarePaginator(
                $paginatedUsers,
                total: $totalEntries,
                items: $items,
            ),
        );

        return Inertia::render('game/[game]/top-achievers', $props);
    }
}
