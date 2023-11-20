<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Http\Controller;
use App\Platform\Enums\PlayerStatType;
use App\Platform\Models\Game;
use App\Platform\Models\PlayerStat;
use App\Platform\Models\System;
use App\Site\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

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
            'filter.kind' => 'sometimes|string',
        ]);

        $targetSystemId = (int) ($validatedData['filter']['system'] ?? 0);
        [$gameKindFilterOptions, $leaderboardKind] = $this->determineGameKindFilterOptions($validatedData);

        // Now get the current page's rows.
        $currentPage = $validatedData['page']['number'] ?? 1;
        $offset = (int) ($currentPage - 1) * $this->pageSize;

        $allBeatenGameAwardsRankedRows = $this->getAggregatedLeaderboardData(
            $offset,
            $gameKindFilterOptions,
            $targetSystemId,
        );

        // We need to know how many rows there are, otherwise the
        // paginator can't determine what the max page number should be.
        $rankedRowsCount = $allBeatenGameAwardsRankedRows->count();

        // Where does the authed user currently rank?
        $isUserOnCurrentPage = false;
        $myRankingData = null;
        $myUsername = null;
        $userPageNumber = null;
        $me = Auth::user() ?? null;
        if ($me) {
            $myRankingData = $this->getUserRankingData($me->id, $allBeatenGameAwardsRankedRows);
            $userPageNumber = (int) $myRankingData['userPageNumber'];
            $isUserOnCurrentPage = (int) $currentPage === $userPageNumber;
        }

        $currentPageRows = $allBeatenGameAwardsRankedRows->slice($offset, $this->pageSize);

        $paginator = new LengthAwarePaginator($currentPageRows, $rankedRowsCount, $this->pageSize, $currentPage, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);

        // Grab all the systems so we can build the system filter options.
        $allSystems = System::orderBy('Name')->get(['ID', 'Name']);

        return view('platform.beaten-games-leaderboard-page', [
            'allSystems' => $allSystems,
            'gameKindFilterOptions' => $gameKindFilterOptions,
            'isUserOnCurrentPage' => $isUserOnCurrentPage,
            'leaderboardKind' => $leaderboardKind,
            'myRankingData' => $myRankingData,
            'myUsername' => $myUsername,
            'paginator' => $paginator,
            'selectedConsoleId' => $targetSystemId,
            'userPageNumber' => $userPageNumber,
        ]);
    }

    private function attachRankingRowsMetadata(mixed $rankingRows, ?int $limit = null, ?int $offset = null): mixed
    {
        // Only attach metadata to the current page's rows. Leave the rest alone.
        // After we've attached metadata, we'll merge this back in to the original collection.
        $currentPageRows = $rankingRows->slice($offset, $limit);

        // Fetch all the usernames for the current page.
        $userIds = $currentPageRows->pluck('user_id')->unique();
        $usernames = User::whereIn('ID', $userIds)->get(['ID', 'User'])->keyBy('ID');

        // Fetch all the game metadata for the current page.
        $gameIds = $currentPageRows->pluck('last_game_id')->unique();
        $gameData = Game::whereIn('ID', $gameIds)->get(['ID', 'Title', 'ImageIcon', 'ConsoleID'])->keyBy('ID');

        // Fetch all the console metadata for the current page.
        $consoleIds = $gameData->pluck('ConsoleID')->unique()->filter();
        $consoleData = System::whereIn('ID', $consoleIds)->get(['ID', 'Name'])->keyBy('ID');

        // Stitch all the fetched metadata back onto the aggregate rankings.
        $currentPageRows->transform(function ($ranking) use ($usernames, $gameData, $consoleData) {
            $ranking->User = $usernames[$ranking->user_id]->User ?? null;
            $ranking->GameTitle = $gameData[$ranking->last_game_id]->Title ?? null;
            $ranking->GameIcon = $gameData[$ranking->last_game_id]->ImageIcon ?? null;

            $consoleId = $gameData[$ranking->last_game_id]->ConsoleID ?? null;
            $ranking->ConsoleName = $consoleId ? $consoleData[$consoleId]->Name ?? null : null;

            return $ranking;
        });

        // Replace each element in the original collection with its modified version.
        foreach ($currentPageRows as $key => $modifiedRanking) {
            $rankingRows[$offset + $key] = $modifiedRanking;
        }

        return $rankingRows;
    }

    private function buildAggregatedLeaderboardQuery(array $gameKindFilterOptions = [], ?int $targetSystemId = null): mixed
    {
        $includedTypes = $this->getIncludedTypes($gameKindFilterOptions);

        $aggregateSubquery = PlayerStat::selectRaw(
            'user_id,
            SUM(value) AS total_awards,
            MAX(last_affected_at) AS last_beaten_date'
        )
            ->when($targetSystemId, function ($query) use ($targetSystemId) {
                return $query->where('system_id', $targetSystemId);
            }, function ($query) {
                return $query->whereNull('system_id');
            })
            ->whereIn('type', $includedTypes)
            ->groupBy('user_id');

        $query = PlayerStat::selectRaw(
            'sub.user_id, 
            MAX(player_stats.last_game_id) AS last_game_id, 
            MAX(player_stats.last_affected_at) as last_beaten_date, 
            sub.total_awards, 
            RANK() OVER (ORDER BY sub.total_awards DESC) as rank_number,
            ROW_NUMBER() OVER (ORDER BY sub.total_awards DESC, sub.last_beaten_date ASC) as leaderboard_row_number'
        )
            ->joinSub($aggregateSubquery, 'sub', function ($join) use ($targetSystemId) {
                $join->on('sub.user_id', '=', 'player_stats.user_id')
                    ->on('sub.last_beaten_date', '=', 'player_stats.last_affected_at');

                if (isset($targetSystemId) && $targetSystemId > 0) {
                    $join->where('player_stats.system_id', '=', $targetSystemId);
                } else {
                    $join->whereNull('player_stats.system_id');
                }
            })
            ->groupBy('sub.user_id')
            ->orderBy('sub.total_awards', 'desc')
            ->orderBy('sub.last_beaten_date', 'asc');

        return $query;
    }

    private function determineGameKindFilterOptions(array $validatedData): array
    {
        $allFilterKeys = ['retail', 'hacks', 'homebrew', 'unlicensed', 'prototypes', 'demos'];

        // As a safeguard, set everything to false by default.
        $gameKindFilterOptions = array_fill_keys($allFilterKeys, false);

        // Show retail games only by default. It will be the default filter choice.
        $selectedKind = $validatedData['filter']['kind'] ?? 'retail';

        switch ($selectedKind) {
            case 'retail':
                $gameKindFilterOptions['retail'] = true;
                $gameKindFilterOptions['unlicensed'] = true;
                break;
            case 'homebrew':
                $gameKindFilterOptions['homebrew'] = true;
                break;
            case 'hacks':
                $gameKindFilterOptions['hacks'] = true;
                break;
            case 'all':
                $gameKindFilterOptions = array_fill_keys($allFilterKeys, true);
                break;
        }

        return [$gameKindFilterOptions, $selectedKind];
    }

    /**
     * @return Collection<int, array|object>
     */
    private function getAggregatedLeaderboardData(
        int $currentOffset,
        array $gameKindFilterOptions,
        ?int $targetSystemId = null,
    ): Collection {
        // Fetch the aggregated leaderboard.
        $rankings = $this->buildAggregatedLeaderboardQuery($gameKindFilterOptions, $targetSystemId)->get();

        // Fetch extraneous metadata without doing joins.
        // Joins are expensive - doing this as separate queries
        // shaves a significant amount of time from page load.
        return $this->attachRankingRowsMetadata($rankings, $this->pageSize, $currentOffset);
    }

    private function getIncludedTypes(array $gameKindFilterOptions = []): array
    {
        $includedTypes = [];

        if ($gameKindFilterOptions['retail']) {
            $includedTypes[] = PlayerStatType::GamesBeatenHardcoreRetail;
        }
        if ($gameKindFilterOptions['hacks']) {
            $includedTypes[] = PlayerStatType::GamesBeatenHardcoreHacks;
        }
        if ($gameKindFilterOptions['homebrew']) {
            $includedTypes[] = PlayerStatType::GamesBeatenHardcoreHomebrew;
        }
        if ($gameKindFilterOptions['unlicensed']) {
            $includedTypes[] = PlayerStatType::GamesBeatenHardcoreUnlicensed;
        }
        if ($gameKindFilterOptions['prototypes']) {
            $includedTypes[] = PlayerStatType::GamesBeatenHardcorePrototypes;
        }
        if ($gameKindFilterOptions['demos']) {
            $includedTypes[] = PlayerStatType::GamesBeatenHardcoreDemos;
        }

        return $includedTypes;
    }

    /**
     * @param Collection<int, array|object> $rankedRows
     */
    private function getUserRankingData(int $userId, Collection $rankedRows): array
    {
        // Retrieve the user's row from the collection
        /** @var array|object|null $userRow */
        $userRow = collect($rankedRows)->firstWhere('user_id', $userId);

        if (!$userRow) {
            return [
                'userRankingData' => null,
                'userRank' => null,
                'userPageNumber' => null,
            ];
        }

        // Create a collection with just the user's row. We'll attach metadata to this.
        $userRowCollection = collect([$userRow]);

        // Attach the metadata just like we do with all the other rows on the page.
        $userRowWithMetadata = $this->attachRankingRowsMetadata($userRowCollection)->first();

        // Calculate the user's page number on the leaderboard.
        // We're using the user's index in the $rankedRows collection to determine their
        // page number instead of directly dividing their leaderboard_row_number by pageSize.
        // This avoids an issue related to floating-point division which can result in the
        // calculated userPageNumber being incorrect.
        $userIndex = $rankedRows->search(function ($item) use ($userId) {
            return is_object($item) ? $item->user_id === $userId : 1;
        });
        $userPageNumber = ceil(($userIndex + 1) / $this->pageSize);

        // Return the formatted data.
        return [
            'userRankingData' => $userRowWithMetadata,
            'userRank' => $userRowWithMetadata ? $userRowWithMetadata->rank_number : null,
            'userPageNumber' => $userPageNumber,
        ];
    }
}
