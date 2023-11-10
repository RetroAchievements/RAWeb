<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\AwardType;
use App\Http\Controller;
use App\Platform\Enums\UnlockMode;
use App\Platform\Models\System;
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

        $beatenGameAwardsRankedRows = $this->getLeaderboardDataForCurrentPage($offset, $gameKindFilterOptions, $targetSystemId);

        // We need to know how many rows there are, otherwise the
        // paginator can't determine what the max page number should be.
        $rankedRowsCount = $this->getLeaderboardRowCount();

        // Where does the authed user currently rank?
        // This is a separate query that doesn't include the page/offset.
        $isUserOnCurrentPage = false;
        $myRankingData = null;
        $myUsername = null;
        $userPageNumber = null;
        $me = Auth::user() ?? null;
        if ($me) {
            $myUsername = $me->User;
            $myRankingData = $this->getUserRankingData($myUsername, $gameKindFilterOptions, $targetSystemId);
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

    private function buildLeaderboardBaseSubquery(array $gameKindFilterOptions = [], ?int $targetSystemId = null): mixed
    {
        $subquery = DB::table('SiteAwards as sa')
            ->join('GameData as gd', 'sa.AwardData', '=', 'gd.ID')
            ->join('Console as c', 'gd.ConsoleID', '=', 'c.ID')
            ->join('UserAccounts as ua', 'sa.User', '=', 'ua.User')
            ->select(
                'ua.User as User',
                DB::raw('FIRST_VALUE(gd.ID) OVER (PARTITION BY ua.User ORDER BY sa.AwardDate desc) as most_recent_game_id'),
                'gd.Title',
                'gd.ImageIcon',
                'c.name as ConsoleName',
                'sa.AwardDate',
            )
            ->where('sa.AwardType', AwardType::GameBeaten)
            ->where('sa.AwardDataExtra', UnlockMode::Hardcore)
            ->where('ua.Untracked', 0)
            ->where('gd.Title', 'not like', '~Subset~%')
            ->where('gd.Title', 'not like', '~Test Kit~%')
            ->where('gd.Title', 'not like', '~Multi~%')
            ->whereNotIn('gd.ConsoleID', [100, 101]);

        if ($targetSystemId) {
            $subquery->where('gd.ConsoleID', $targetSystemId);
        }

        if (!$gameKindFilterOptions['retail']) {
            // Exclude all games that don't have two "~" characters in their title.
            $subquery->whereRaw('LENGTH(gd.Title) - LENGTH(REPLACE(gd.Title, "~", "")) >= 2');
        }

        if (!$gameKindFilterOptions['hacks']) {
            $subquery->where('gd.Title', 'not like', '~Hack~%');
        }

        if (!$gameKindFilterOptions['homebrew']) {
            $subquery->where('gd.Title', 'not like', '~Homebrew~%')
                // Exclude Arduboy, WASM-4, and Uzebox. These consoles exclusively have homebrew games.
                ->whereNotIn('gd.ConsoleID', [71, 72, 80]);
        }

        if (!$gameKindFilterOptions['unlicensed']) {
            $subquery->where('gd.Title', 'not like', '~Unlicensed~%');
        }

        if (!$gameKindFilterOptions['prototypes']) {
            $subquery->where('gd.Title', 'not like', '~Prototype~%');
        }

        if (!$gameKindFilterOptions['demos']) {
            $subquery->where('gd.Title', 'not like', '~Demo~%');
        }

        return $subquery;
    }

    private function buildRankingsSubquery(array $gameKindFilterOptions, ?int $targetSystemId = null): mixed
    {
        $subquery = $this->buildLeaderboardBaseSubquery($gameKindFilterOptions, $targetSystemId);

        /** @var string $subqueryTable */
        $subqueryTable = DB::raw("({$subquery->toSql()}) as s");

        return DB::table($subqueryTable)
            ->mergeBindings($subquery)
            ->select(
                'User',
                DB::raw('RANK() OVER (ORDER BY COUNT(s.User) DESC) as rank_number'),
                DB::raw('ROW_NUMBER() OVER (ORDER BY COUNT(s.User) DESC) as leaderboard_row_number'),
                DB::raw('COUNT(s.User) as total_awards'),
                'most_recent_game_id',
                'Title as GameTitle',
                'ImageIcon as GameIcon',
                'ConsoleName',
                DB::raw('MAX(AwardDate) as last_beaten_date'),
            )
            ->orderBy('rank_number')
            ->groupBy('User');
    }

    private function getLeaderboardRowCount(): int
    {
        // SQLite, which is used for integration tests, doesn't support FOUND_ROWS().
        // We'll naively return 25 for now.
        if (DB::getDriverName() === 'sqlite') {
            return 25;
        }

        return (int) DB::select(
            (string) DB::raw('SELECT FOUND_ROWS() as count')
        )[0]->count;
    }

    private function getUserRankingData(string $username, array $gameKindFilterOptions, ?int $targetSystemId = null): array
    {
        $subquery = $this->buildRankingsSubquery($gameKindFilterOptions, $targetSystemId);

        /** @var string $subqueryTable */
        $subqueryTable = DB::raw("({$subquery->toSql()}) as b");

        $result = DB::table($subqueryTable)
            ->mergeBindings($subquery)
            ->where('User', $username)
            ->get();

        $userRankingData = $result->isEmpty() ? null : $result->get(0);

        return [
            'userRankingData' => $userRankingData,
            'userRank' => isset($userRankingData) ? $userRankingData->rank_number : null,
            'userPageNumber' => isset($userRankingData)
                ? ceil($userRankingData->leaderboard_row_number / $this->pageSize)
                : null,
        ];
    }

    private function getLeaderboardDataForCurrentPage(int $currentOffset, array $gameKindFilterOptions, ?int $targetSystemId = null): mixed
    {
        $subquery = $this->buildRankingsSubquery($gameKindFilterOptions, $targetSystemId);

        /** @var string $subqueryTable */
        $subqueryTable = DB::raw("({$subquery->toSql()}) as b");

        $result = applyFoundRows(DB::table($subqueryTable)->mergeBindings($subquery))
            ->orderBy('rank_number')
            ->orderBy('last_beaten_date')
            ->offset($currentOffset)
            ->limit($this->pageSize)
            ->get();

        return $result;
    }
}
