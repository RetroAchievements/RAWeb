<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\Game;
use App\Models\PlayerStat;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\PlayerStatType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BeatenGamesLeaderboardService
{
    private int $pageSize = 25;

    public function buildViewData(Request $request): array|RedirectResponse
    {
        $validatedData = $request->validate([
            'page.number' => 'sometimes|integer|min:1',
            'filter.system' => 'sometimes|integer',
            'filter.kind' => 'sometimes|string',
            'filter.user' => 'sometimes|string',
        ]);

        $foundUserByFilter = null;
        $isUserFilterSet = isset($validatedData['filter']['user']);
        if ($isUserFilterSet) {
            $foundUserByFilter = User::byDisplayName($validatedData['filter']['user'])->first();

            if (!$foundUserByFilter) {
                return ['redirect' => route('ranking.beaten-games')];
            }
        }

        $targetSystemId = (int) ($validatedData['filter']['system'] ?? 0);
        [$gameKindFilterOptions, $leaderboardKind] = $this->determineGameKindFilterOptions(
            $validatedData,
            $targetSystemId,
        );

        // Now get the current page's rows.
        $currentPage = (int) ($validatedData['page']['number'] ?? 1);

        // Fetch the paginated leaderboard data.
        $paginator = $this->getAggregatedLeaderboardData(
            $request,
            $currentPage,
            $gameKindFilterOptions,
            $targetSystemId,
        );

        // Determine where the authed or target user currently ranks.
        $isUserOnCurrentPage = false;
        $targetUserRankingData = null;
        $userPageNumber = null;
        $targetUser = $foundUserByFilter ?? Auth::user() ?? null;
        if ($targetUser) {
            $targetUserRankingData = $this->getUserRankingData($targetUser->id, $gameKindFilterOptions, $targetSystemId);
            if ($targetUserRankingData) {
                $userPageNumber = (int) $targetUserRankingData['userPageNumber'];
                $isUserOnCurrentPage = (int) $currentPage === $userPageNumber;
            } else {
                $isUserOnCurrentPage = false;
            }
        }

        // If there's an active user filter and we have their ranking data, redirect to that user's page and remove the filter.
        if ($targetUser && $isUserFilterSet) {
            if ($targetUserRankingData) {
                return ['redirect' => route('ranking.beaten-games', ['page[number]' => $userPageNumber])];
            }

            // We could end up here for a number of reasons, such as
            // the target user being untracked or being softcore-only.
            return ['redirect' => route('ranking.beaten-games')];
        }

        // Grab all the systems so we can build the system filter options.
        $allSystems = System::gameSystems()->active()->orderBy('Name')->get(['ID', 'Name']);

        return [
            'allSystems' => $allSystems,
            'gameKindFilterOptions' => $gameKindFilterOptions,
            'isUserOnCurrentPage' => $isUserOnCurrentPage,
            'leaderboardKind' => $leaderboardKind,
            'targetUserRankingData' => $targetUserRankingData,
            'paginator' => $paginator,
            'selectedConsoleId' => $targetSystemId,
            'userPageNumber' => $userPageNumber,
        ];
    }

    private function attachRankingRowsMetadata(mixed $rankingRows): void
    {
        // Fetch all the usernames for the current page.
        $userIds = $rankingRows->pluck('user_id')->unique();
        $usernames = User::whereIn('ID', $userIds)->get(['ID', 'User'])->keyBy('ID');

        // Fetch all the game metadata for the current page.
        $gameIds = $rankingRows->pluck('last_game_id')->unique()->filter();
        $gameData = Game::whereIn('ID', $gameIds)->get(['ID', 'Title', 'ImageIcon', 'ConsoleID'])->keyBy('ID');

        // Fetch all the console metadata for the current page.
        $consoleIds = $gameData->pluck('ConsoleID')->unique()->filter();
        $consoleData = System::whereIn('ID', $consoleIds)->get(['ID', 'Name'])->keyBy('ID');

        // Stitch all the fetched metadata back onto the aggregate rankings.
        $rankingRows->transform(function ($ranking) use ($usernames, $gameData, $consoleData) {
            $ranking->User = $usernames[$ranking->user_id]->User ?? null;
            $ranking->GameTitle = $gameData[$ranking->last_game_id]->Title ?? null;
            $ranking->GameIcon = $gameData[$ranking->last_game_id]->ImageIcon ?? null;

            $consoleId = $gameData[$ranking->last_game_id]->ConsoleID ?? null;
            $ranking->ConsoleName = $consoleId ? $consoleData[$consoleId]->Name ?? null : null;

            return $ranking;
        });
    }

    private function buildAggregatedLeaderboardQuery(array $gameKindFilterOptions = [], ?int $targetSystemId = null): mixed
    {
        $includedTypes = $this->getIncludedTypes($gameKindFilterOptions);

        $aggregateSubquery = PlayerStat::selectRaw(
            'user_id,
            SUM(value) AS total_awards,
            MAX(stat_updated_at) AS last_beaten_date'
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
            MAX(CASE WHEN player_stats.type IN (\'' . implode("', '", $includedTypes) . '\') THEN player_stats.last_game_id ELSE NULL END) AS last_game_id,
            MAX(CASE WHEN player_stats.type IN (\'' . implode("', '", $includedTypes) . '\') THEN player_stats.stat_updated_at ELSE NULL END) as last_beaten_date,
            sub.total_awards,
            RANK() OVER (ORDER BY sub.total_awards DESC) as rank_number,
            ROW_NUMBER() OVER (ORDER BY sub.total_awards DESC, sub.last_beaten_date ASC) as leaderboard_row_number'
        )
            ->joinSub($aggregateSubquery, 'sub', function ($join) use ($targetSystemId) {
                $join->on('sub.user_id', '=', 'player_stats.user_id')
                    ->on('sub.last_beaten_date', '=', 'player_stats.stat_updated_at');

                if (isset($targetSystemId) && $targetSystemId > 0) {
                    $join->where('player_stats.system_id', '=', $targetSystemId);
                } else {
                    $join->whereNull('player_stats.system_id');
                }
            })
            ->whereIn('player_stats.type', $includedTypes)
            ->groupBy('sub.user_id', 'sub.total_awards', 'sub.last_beaten_date')
            ->orderBy('sub.total_awards', 'desc')
            ->orderBy('sub.last_beaten_date', 'asc');

        return $query;
    }

    private function determineGameKindFilterOptions(array $validatedData, int $targetSystemId): array
    {
        $allFilterKeys = ['retail', 'hacks', 'homebrew', 'unlicensed', 'prototypes', 'demos'];

        // As a safeguard, set everything to false by default.
        $gameKindFilterOptions = array_fill_keys($allFilterKeys, false);

        // Homebrew systems do not have 'retail' games.
        $isHomebrewSystem = System::isHomebrewSystem($targetSystemId);
        $fallbackKind = $isHomebrewSystem ? 'homebrew' : 'retail';

        // Show retail games only by default. It will be the default filter choice.
        $selectedKind = $validatedData['filter']['kind'] ?? $fallbackKind;

        switch ($selectedKind) {
            case 'retail':
                if (!$isHomebrewSystem) {
                    $gameKindFilterOptions['retail'] = true;
                }
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
                if ($isHomebrewSystem) {
                    $gameKindFilterOptions['retail'] = false;
                }

                break;
        }

        return [$gameKindFilterOptions, $selectedKind];
    }

    /**
     * Fetch the paginated leaderboard data.
     */
    private function getAggregatedLeaderboardData(
        Request $request,
        int $currentPage,
        array $gameKindFilterOptions,
        ?int $targetSystemId = null,
    ): mixed {
        // Fetch the aggregated leaderboard with pagination.
        $paginator = $this->buildAggregatedLeaderboardQuery($gameKindFilterOptions, $targetSystemId)
            ->paginate($this->pageSize, ['*'], 'page[number]', $currentPage);

        // Fetch extraneous metadata without doing joins.
        // Joins are expensive - doing this as separate queries
        // shaves a significant amount of time from page load.
        $items = $paginator->getCollection();
        $this->attachRankingRowsMetadata($items);

        // Set the path and query parameters for pagination links.
        $paginator->withPath($request->url());
        $paginator->appends($request->query());

        return $paginator;
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
     * Calculates the user's ranking data without loading the entire leaderboard.
     */
    private function getUserRankingData(
        int $userId,
        array $gameKindFilterOptions,
        ?int $targetSystemId = null
    ): ?array {
        $query = $this->buildAggregatedLeaderboardQuery($gameKindFilterOptions, $targetSystemId);

        // Create a subquery to get the complete ranking data.
        $rankingSubquery = $query->toSql();

        // Use the subquery to get the specific user's data.
        $userData = DB::table(DB::raw("({$rankingSubquery}) as rankings"))
            ->mergeBindings($query->getQuery())
            ->where('user_id', $userId)
            ->first();

        if (!$userData) {
            return null;
        }

        // Attach metadata.
        $userDataWithMetadata = collect([$userData]);
        $this->attachRankingRowsMetadata($userDataWithMetadata);
        $userDataWithMetadata = $userDataWithMetadata->first();

        // Calculate the user's page number based on row number.
        $userPageNumber = (int) ceil($userData->leaderboard_row_number / $this->pageSize);

        return [
            'userRankingData' => $userDataWithMetadata,
            'userRank' => $userData->rank_number,
            'userPageNumber' => $userPageNumber,
        ];
    }
}
