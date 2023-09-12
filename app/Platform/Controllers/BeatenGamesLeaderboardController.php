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

        // Where do I currently rank? This is a separate query that doesn't include the page/offset.
        $me = Auth::user() ?? null;
        $myRankingData = null;
        if ($me) {
            $myRankingData = $this->buildUserRanking2($me, $targetSystemId, $gameKindFilterOptions);
        }

        // Now get the current page's rows.
        $currentPage = $validatedData['page']['number'] ?? 1;
        $offset = (int) ($currentPage - 1) * $this->pageSize;
        $startingRank = (int) ($currentPage - 1) * $this->pageSize + 1;

        $beatenGameAwardsRankedRows = $this->buildBaseLeaderboardQuery2($offset, $targetSystemId, $gameKindFilterOptions);

        // We also need to know how many rows there are, otherwise the
        // paginator can't determine what the max page number should be.
        $rankedRowsCount = $this->buildLeaderboardRowCountQuery($targetSystemId, $gameKindFilterOptions);

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

    private function buildLeaderboardRowCountQuery(?int $targetSystemId = null, array $gameKindFilterOptions): mixed
    {
        $subquery = DB::table('SiteAwards as sa')
            ->join('GameData as gd', 'sa.AwardData', '=', 'gd.ID')
            ->join('Console as c', 'gd.ConsoleID', '=', 'c.ID')
            ->join('UserAccounts as ua', 'sa.User', '=', 'ua.User')
            ->select('ua.User as AuthUser',
                DB::raw('FIRST_VALUE(gd.ID) OVER (PARTITION BY ua.User ORDER BY sa.AwardDate desc) as most_recent_game_id'),
                'gd.Title',
                'c.name as ConsoleName',
                'sa.AwardDate',
            )
            ->where('sa.AwardType', 8)
            ->where('sa.AwardDataExtra', 1)
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
    
        $ranked = DB::table(DB::raw("({$subquery->toSql()}) as s"))
            ->mergeBindings($subquery)
            ->select('AuthUser',
                DB::raw('RANK() OVER (ORDER BY COUNT(s.AuthUser) DESC) as RankNum'),
                DB::raw('COUNT(s.AuthUser) as total'),
                'most_recent_game_id',
                'Title',
                'ConsoleName',
                'AwardDate',
            )
            ->groupBy('AuthUser');
    
        $result = DB::table(DB::raw("({$ranked->toSql()}) as b"))
            ->mergeBindings($ranked)
            ->select(DB::raw('count(*) as total_row_count'))
            ->get();

        return $result->get(0)->total_row_count;
    }

    private function buildUserRanking2(User $user, ?int $targetSystemId = null, array $gameKindFilterOptions): mixed
    {
        $subquery = DB::table('SiteAwards as sa')
            ->join('GameData as gd', 'sa.AwardData', '=', 'gd.ID')
            ->join('Console as c', 'gd.ConsoleID', '=', 'c.ID')
            ->join('UserAccounts as ua', 'sa.User', '=', 'ua.User')
            ->select('ua.User as User',
                DB::raw('FIRST_VALUE(gd.ID) OVER (PARTITION BY ua.User ORDER BY sa.AwardDate desc) as most_recent_game_id'),
                'gd.Title',
                'gd.ImageIcon',
                'c.name as ConsoleName',
                'sa.AwardDate',
            )
            ->where('sa.AwardType', 8)
            ->where('sa.AwardDataExtra', 1)
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
    
        $ranked = DB::table(DB::raw("({$subquery->toSql()}) as s"))
            ->mergeBindings($subquery)
            ->select('User',
                DB::raw('RANK() OVER (ORDER BY COUNT(s.User) DESC) as rank_number'),
                DB::raw('COUNT(s.User) as total_awards'),
                'most_recent_game_id',
                'Title as GameTitle',
                'ImageIcon as GameIcon',
                'ConsoleName',
                'AwardDate as last_beaten_date',
            )
            ->groupBy('User');
    
        $result = DB::table(DB::raw("({$ranked->toSql()}) as b"))
            ->mergeBindings($ranked)
            ->where('User', 'SirWillis')
            ->get();

        $userRankingData = $result->get(0);

        return ['userRankingData' => $userRankingData, 'userRank' => $userRankingData->rank_number];
    }

    private function buildBaseLeaderboardQuery2(int $currentOffset, ?int $targetSystemId = null, array $gameKindFilterOptions): mixed
    {
        $subquery = DB::table('SiteAwards as sa')
            ->join('GameData as gd', 'sa.AwardData', '=', 'gd.ID')
            ->join('Console as c', 'gd.ConsoleID', '=', 'c.ID')
            ->join('UserAccounts as ua', 'sa.User', '=', 'ua.User')
            ->select('ua.User as User',
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
    
        $ranked = DB::table(DB::raw("({$subquery->toSql()}) as s"))
            ->mergeBindings($subquery)
            ->select(
                'User',
                DB::raw('RANK() OVER (ORDER BY COUNT(s.User) DESC) as rank_number'),
                DB::raw('COUNT(s.User) as total_awards'),
                'most_recent_game_id',
                'Title as GameTitle',
                'ImageIcon as GameIcon',
                'ConsoleName',
                'AwardDate as last_beaten_date',
            )
            ->groupBy('User');
    
        $result = DB::table(DB::raw("({$ranked->toSql()}) as b"))
            ->mergeBindings($ranked)
            ->offset($currentOffset)
            ->limit($this->pageSize)
            ->get();

        return $result;
    }

    private function buildFilteredGamesQuery(?int $targetSystemId = null, array $gameKindFilterOptions): mixed
    {
        $query = PlayerBadge::from('SiteAwards as filteredGames')
            ->select(
                'filteredGames.*',
                'gd.*',
                'c.name AS ConsoleName',
                'ua.User AS AuthUser',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY gd.ID ORDER BY filteredGames.AwardDate desc) rownum')
            )
            ->where('filteredGames.AwardType', AwardType::GameBeaten)
            ->where('filteredGames.AwardDataExtra', UnlockMode::Hardcore)
            ->join('GameData as gd', 'filteredGames.AwardData', '=', 'gd.ID')
            ->join('Console as c', 'gd.ConsoleID', '=', 'c.ID')
            ->join('UserAccounts as ua', 'filteredGames.User', '=', 'ua.User')
            ->where('ua.Untracked', false)
            ->where('gd.Title', 'not like', '~Subset~%')
            ->where('gd.Title', 'not like', '~Test Kit~%')
            ->where('gd.Title', 'not like', '~Multi~%')
            ->whereNotIn('gd.ConsoleID', [100, 101]) // Exclude events and hubs.
            ->orderBy('filteredGames.AwardDate', 'desc');

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

        if (!$gameKindFilterOptions['demos']) {
            $query->where('gd.Title', 'not like', '~Demo~%');
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
                'aggregated.ID AS most_recent_game_id',
                'aggregated.Title AS GameTitle',
                'aggregated.ImageIcon AS GameIcon',
                'aggregated.ConsoleName AS ConsoleName'
            );

        return $query->groupBy('aggregated.User')
            ->orderBy('total_awards', 'desc')
            ->orderBy('last_beaten_date', 'asc');
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
