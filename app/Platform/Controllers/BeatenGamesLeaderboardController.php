<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Enums\AwardType;
use App\Http\Controller;
use App\Platform\Enums\UnlockMode;
use App\Platform\Models\PlayerBadge;
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

        $targetSystemId = (int) $request->input('filter.system', 0);

        $allowRetailQuery = $request->input('filter.retail');
        $allowHacksQuery = $request->input('filter.hacks');
        $allowHomebrewQuery = $request->input('filter.homebrew');
        $allowUnlicensedQuery = $request->input('filter.unlicensed');
        $allowPrototypesQuery = $request->input('filter.prototypes');

        $gameKindFilterOptions = [
            'retail' => $allowRetailQuery !== 'false',
            'hacks' => $allowHacksQuery !== 'false',
            'homebrew' => $allowHomebrewQuery !== 'false',
            'unlicensed' => $allowUnlicensedQuery !== 'false',
            'prototypes' => $allowPrototypesQuery !== 'false',
        ];

        // Where do I currently rank? This is a separate query that doesn't include the page/offset.
        $me = Auth::user() ?? null;
        $myRankingData = null;
        if ($me) {
            $myRankingData = $this->buildUserRanking($me, $targetSystemId, $gameKindFilterOptions);
        }

        // Now get the current page's rows.
        $currentPage = $request->input('page.number', 1);
        $offset = (int) ($currentPage - 1) * $this->pageSize;
        $startingRank = (int) ($currentPage - 1) * $this->pageSize + 1;

        $beatenGameAwardsRankedRows = $this->buildBeatenGameAwardsRankings($offset, $targetSystemId, $gameKindFilterOptions);

        // We also need to know how many rows there are, otherwise the
        // paginator can't determine what the max page number should be.
        $rankedRowsCount = $this->buildFilteredGamesQuery($targetSystemId, $gameKindFilterOptions)
            ->distinct('filteredGames.user')
            ->count('filteredGames.user');

        $paginator = new LengthAwarePaginator($beatenGameAwardsRankedRows, $rankedRowsCount, $this->pageSize, $currentPage, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        // Grab all the systems so we can build the system filter options.
        $allSystems = System::orderBy('Name')->get(['ID', 'Name']);

        return view('platform.beaten-games-leaderboard-page', [
            'allSystems' => $allSystems,
            'gameKindFilterOptions' => $gameKindFilterOptions,
            'myRankingData' => $myRankingData,
            'paginator' => $paginator,
            'selectedConsoleId' => $targetSystemId,
            'startingRank' => $startingRank,
        ]);
    }

    private function buildFilteredGamesQuery(?int $targetSystemId = null, array $gameKindFilterOptions): mixed
    {
        $query = PlayerBadge::from('SiteAwards as filteredGames')
            ->select('filteredGames.*', 'gd.*', 'c.name AS ConsoleName', 'ua.User AS AuthUser')
            ->where('filteredGames.AwardType', AwardType::GameBeaten)
            ->where('filteredGames.AwardDataExtra', UnlockMode::Hardcore)
            ->join('GameData as gd', 'filteredGames.AwardData', '=', 'gd.ID')
            ->join('Console as c', 'gd.ConsoleID', '=', 'c.ID')
            ->join('UserAccounts as ua', 'filteredGames.User', '=', 'ua.User')
            ->where('ua.Untracked', false)
            ->where('gd.Title', 'not like', '~Demo~%')
            ->where('gd.Title', 'not like', '~Subset~%')
            ->where('gd.Title', 'not like', '~Test Kit~%')
            ->where('gd.Title', 'not like', '~Multi~%')
            ->whereNotIn('gd.ConsoleID', [100, 101]); // Exclude events and hubs.

        if ($targetSystemId) {
            $query->where('c.ID', $targetSystemId);
        }

        if (!$gameKindFilterOptions['retail']) {
            // Exclude all games that don't have two "~" characters in their title.
            $query->whereRaw('LENGTH(gd.Title) - LENGTH(REPLACE(gd.Title, "~", "")) >= 2');
        }

        if (!$gameKindFilterOptions['hacks']) {
            $query->where('gd.Title', 'not like', '~Hack~%');
        }

        if (!$gameKindFilterOptions['homebrew']) {
            $query->where('gd.Title', 'not like', '~Homebrew~%')
                // Exclude Arduboy, WASM-4, and Uzebox. These consoles exclusively have homebrew games.
                ->whereNotIn('gd.ConsoleID', [71, 72, 80]);
        }

        if (!$gameKindFilterOptions['unlicensed']) {
            $query->where('gd.Title', 'not like', '~Unlicensed~%');
        }

        if (!$gameKindFilterOptions['prototypes']) {
            $query->where('gd.Title', 'not like', '~Prototype~%');
        }

        return $query;
    }

    private function buildBaseLeaderboardQuery(?int $targetSystemId = null, array $gameKindFilterOptions): mixed
    {
        // Create the subquery that applies all the filters.
        $filteredGames = $this->buildFilteredGamesQuery($targetSystemId, $gameKindFilterOptions);

        // Now, use the filtered subquery to compute the FIRST_VALUE and other aggregations.
        $query = PlayerBadge::fromSub($filteredGames, 'aggregated')
            ->select(
                'aggregated.User',
                DB::raw('count(*) as total_awards'),
                DB::raw('MAX(aggregated.AwardDate) as last_beaten_date'),
                DB::raw('MIN(aggregated.AwardDate) as earliest_beaten_date_for_count'),
                DB::raw('FIRST_VALUE(aggregated.ID) OVER(PARTITION BY aggregated.User ORDER BY aggregated.AwardDate DESC) as most_recent_game_id'),
                'aggregated.Title AS GameTitle',
                'aggregated.ImageIcon AS GameIcon',
                'aggregated.ConsoleName AS ConsoleName'
            );

        return $query->groupBy('aggregated.User')
            ->orderBy('total_awards', 'desc')
            ->orderBy('earliest_beaten_date_for_count', 'asc')
            ->orderBy('last_beaten_date', 'desc');
    }

    private function buildBeatenGameAwardsRankings(
        int $currentOffset,
        ?int $targetSystemId = null,
        array $gameKindFilterOptions
    ): mixed {
        return $this->buildBaseLeaderboardQuery($targetSystemId, $gameKindFilterOptions)
            ->offset($currentOffset)
            ->limit($this->pageSize)
            ->get();
    }

    private function buildUserRanking(
        User $user,
        ?int $targetSystemId = null,
        array $gameKindFilterOptions,
    ): ?array {
        $allRankings = $this->buildBaseLeaderboardQuery($targetSystemId, $gameKindFilterOptions)->get();
        $userRankingData = $allRankings->firstWhere('User', $user->User);

        if (!$userRankingData) {
            return null;
        }

        $userRank = $allRankings->search(function ($row) use ($user) {
            return $row->User === $user->User;
        }) + 1;

        return ['userRankingData' => $userRankingData, 'userRank' => $userRank];
    }
}
