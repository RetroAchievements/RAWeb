<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Platform\Enums\RankingType;
use App\Platform\Models\Game;
use App\Platform\Models\Ranking;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BeatenGamesLeaderboardController extends Controller
{
    private int $pageSize = 25;

    public function __invoke(Request $request): View
    {
        if (!config('feature.beat')) {
            abort(404);
        }

        $validatedData = $request->validate([
            'page.number' => 'sometimes|integer|min:1',
            'filter.system' => 'sometimes|integer',
            'filter.retail' => 'sometimes|in:true,false',
            'filter.hacks' => 'sometimes|in:true,false',
            'filter.homebrew' => 'sometimes|in:true,false',
            'filter.unlicensed' => 'sometimes|in:true,false',
            'filter.prototypes' => 'sometimes|in:true,false',
            'filter.demos' => 'sometimes|in:true,false',
        ]);

        $targetSystemId = (int) ($validatedData['filter']['system'] ?? 0);

        $gameKindFilterOptions = [
            'retail' => ($validatedData['filter']['retail'] ?? true) !== 'false',
            'hacks' => ($validatedData['filter']['hacks'] ?? true) !== 'false',
            'homebrew' => ($validatedData['filter']['homebrew'] ?? true) !== 'false',
            'unlicensed' => ($validatedData['filter']['unlicensed'] ?? true) !== 'false',
            'prototypes' => ($validatedData['filter']['prototypes'] ?? true) !== 'false',
            'demos' => ($validatedData['filter']['demos'] ?? true) !== 'false',
        ];

        // Now get the current page's rows.
        $currentPage = $validatedData['page']['number'] ?? 1;
        $offset = (int) ($currentPage - 1) * $this->pageSize;

        $beatenGameAwardsRankedRows = $this->getAggregatedLeaderboardDataForCurrentPage($offset, $gameKindFilterOptions, $targetSystemId);

        // We need to know how many rows there are, otherwise the
        // paginator can't determine what the max page number should be.
        $rankedRowsCount = $this->getLeaderboardRowCount($gameKindFilterOptions, $targetSystemId);

        // Where does the authed user currently rank?
        // This is a separate query that doesn't include the page/offset.
        $isUserOnCurrentPage = false;
        $myRankingData = null;
        $myUsername = null;
        $userPageNumber = null;
        $me = Auth::user() ?? null;
        if ($me) {
            $myRankingData = $this->getUserRankingData($me->id, $gameKindFilterOptions, $targetSystemId);
            $userPageNumber = (int) $myRankingData['userPageNumber'];
            $isUserOnCurrentPage = (int) $currentPage === $userPageNumber;
        }

        $paginator = new LengthAwarePaginator($beatenGameAwardsRankedRows, $rankedRowsCount, $this->pageSize, $currentPage, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        // Grab all the systems so we can build the system filter options.
        $allSystems = System::orderBy('Name')->get(['ID', 'Name']);

        return view('platform.beaten-games-leaderboard-page', [
            'allSystems' => $allSystems,
            'gameKindFilterOptions' => $gameKindFilterOptions,
            'isUserOnCurrentPage' => $isUserOnCurrentPage,
            'myRankingData' => $myRankingData,
            'myUsername' => $myUsername,
            'paginator' => $paginator,
            'selectedConsoleId' => $targetSystemId,
            'userPageNumber' => $userPageNumber,
        ]);
    }

    private function buildAggregatedLeaderboardBaseQuery(array $gameKindFilterOptions = [], ?int $targetSystemId = null): mixed
    {
        $query = Ranking::select(
            'user_id',
            'game_id as most_recent_game_id',
            'updated_at as last_beaten_date',
            DB::raw('RANK() OVER (ORDER BY SUM(value) DESC) as rank_number'),
            DB::raw('ROW_NUMBER() OVER (ORDER BY SUM(value) DESC) as leaderboard_row_number'),
            DB::raw('SUM(value) as total_awards'),
        );

        if ($targetSystemId) {
            $query->where('system_id', $targetSystemId);
        } else {
            $query->whereNull('system_id');
        }

        $includedTypes = [];
        if ($gameKindFilterOptions['retail']) {
            $includedTypes[] = [RankingType::GamesBeatenHardcoreRetail];
        }
        if ($gameKindFilterOptions['hacks']) {
            $includedTypes[] = [RankingType::GamesBeatenHardcoreHacks];
        }
        if ($gameKindFilterOptions['homebrew']) {
            $includedTypes[] = [RankingType::GamesBeatenHardcoreHomebrew];
        }
        if ($gameKindFilterOptions['unlicensed']) {
            $includedTypes[] = [RankingType::GamesBeatenHardcoreUnlicensed];
        }
        if ($gameKindFilterOptions['prototypes']) {
            $includedTypes[] = [RankingType::GamesBeatenHardcorePrototypes];
        }
        if ($gameKindFilterOptions['demos']) {
            $includedTypes[] = [RankingType::GamesBeatenHardcoreDemos];
        }
        if (!empty($includedTypes)) {
            $query->whereIn('type', $includedTypes);
        }

        $query->groupBy('user_id');

        return $query;
    }

    private function attachRankingRowsMetadata(mixed $rankingRows): mixed
    {
        // Fetch all the usernames for the current page.
        $userIds = $rankingRows->pluck('user_id')->unique();
        $usernames = User::whereIn('ID', $userIds)->get(['ID', 'User'])->keyBy('ID');

        // Fetch all the game metadata for the current page.
        $gameIds = $rankingRows->pluck('most_recent_game_id')->unique();
        $gameData = Game::whereIn('ID', $gameIds)->get(['ID', 'Title', 'ImageIcon', 'ConsoleID'])->keyBy('ID');

        // Fetch all the console metadata for the current page.
        $consoleIds = $gameData->pluck('ConsoleID')->unique()->filter();
        $consoleData = System::whereIn('ID', $consoleIds)->get(['ID', 'Name'])->keyBy('ID');

        // Stitch all the fetched metadata back onto the aggregate rankings.
        $rankingRows->transform(function ($ranking) use ($usernames, $gameData, $consoleData) {
            $ranking->User = $usernames[$ranking->user_id]->User ?? null;
            $ranking->GameTitle = $gameData[$ranking->most_recent_game_id]->Title ?? null;
            $ranking->GameIcon = $gameData[$ranking->most_recent_game_id]->ImageIcon ?? null;

            $consoleId = $gameData[$ranking->most_recent_game_id]->ConsoleID ?? null;
            $ranking->ConsoleName = $consoleId ? $consoleData[$consoleId]->Name ?? null : null;

            return $ranking;
        });

        return $rankingRows;
    }

    private function getAggregatedLeaderboardDataForCurrentPage(int $currentOffset, array $gameKindFilterOptions, ?int $targetSystemId = null): mixed
    {
        // Fetch the aggregated leaderboard.
        $rankings = $this->buildAggregatedLeaderboardBaseQuery(
            $gameKindFilterOptions,
            $targetSystemId
        )
            ->orderBy('total_awards', 'desc')
            ->offset($currentOffset)
            ->limit($this->pageSize)
            ->get();

        // Fetch extraneous metadata without doing joins.
        // Joins are expensive - doing this as separate queries
        // shaves a significant amount of time from page load.
        return $this->attachRankingRowsMetadata($rankings);
    }

    // FIXME: Use FOUND_ROWS().
    private function getLeaderboardRowCount(array $gameKindFilterOptions, ?int $targetSystemId = null): int
    {
        return $this->buildAggregatedLeaderboardBaseQuery(
            $gameKindFilterOptions,
            $targetSystemId
        )
            ->get()
            ->count();
    }

    private function getUserRankingData(int $userId, array $gameKindFilterOptions, ?int $targetSystemId = null): array
    {
        $baseQuery = $this->buildAggregatedLeaderboardBaseQuery(
            $gameKindFilterOptions,
            $targetSystemId
        );

        // Then, you create an outer query that selects from the base query.
        $result = DB::query()->fromSub($baseQuery, 'sub')
            ->where('sub.user_id', $userId)
            ->get();

        $result = $this->attachRankingRowsMetadata($result);

        $userRankingData = $result->isEmpty() ? null : $result->get(0);

        return [
            'userRankingData' => $userRankingData,
            'userRank' => isset($userRankingData) ? $userRankingData->rank_number : null,
            'userPageNumber' => isset($userRankingData)
                ? ceil($userRankingData->leaderboard_row_number / $this->pageSize)
                : null,
        ];
    }
}
