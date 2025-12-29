<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\Game;
use App\Models\PlayerStatRanking;
use App\Models\System;
use App\Models\User;
use App\Platform\Enums\PlayerStatRankingKind;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            $foundUserByFilter = User::whereName($validatedData['filter']['user'])->first();

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
        $paginator = $this->getLeaderboardData(
            $request,
            $currentPage,
            $leaderboardKind,
            $targetSystemId,
        );

        // Determine where the authed or target user currently ranks.
        $isUserOnCurrentPage = false;
        $targetUserRankingData = null;
        $userPageNumber = null;
        $targetUser = $foundUserByFilter ?? Auth::user() ?? null;
        if ($targetUser) {
            $targetUserRankingData = $this->getUserRankingData($targetUser->id, $leaderboardKind, $targetSystemId);
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
        $usernames = User::whereIn('id', $userIds)->get(['id', 'username'])->keyBy('id');

        // Fetch all the game metadata for the current page.
        $gameIds = $rankingRows->pluck('last_game_id')->unique()->filter();
        $gameData = Game::whereIn('ID', $gameIds)->get(['ID', 'Title', 'ImageIcon', 'ConsoleID'])->keyBy('ID');

        // Fetch all the console metadata for the current page.
        $consoleIds = $gameData->pluck('ConsoleID')->unique()->filter();
        $consoleData = System::whereIn('ID', $consoleIds)->get(['ID', 'Name'])->keyBy('ID');

        // Stitch all the fetched metadata back onto the rankings.
        $rankingRows->transform(function ($ranking) use ($usernames, $gameData, $consoleData) {
            $ranking->User = $usernames[$ranking->user_id]->username ?? null;
            $ranking->GameTitle = $gameData[$ranking->last_game_id]->Title ?? null;
            $ranking->GameIcon = $gameData[$ranking->last_game_id]->ImageIcon ?? null;

            $consoleId = $gameData[$ranking->last_game_id]->ConsoleID ?? null;
            $ranking->ConsoleName = $consoleId ? $consoleData[$consoleId]->Name ?? null : null;

            return $ranking;
        });
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
     * Maps a URL filter kind (eg: 'retail') to the database enum value (eg: 'retail-beaten').
     */
    private function mapFilterKindToDbKind(string $filterKind): PlayerStatRankingKind
    {
        return match ($filterKind) {
            'retail' => PlayerStatRankingKind::RetailBeaten,
            'homebrew' => PlayerStatRankingKind::HomebrewBeaten,
            'hacks' => PlayerStatRankingKind::HacksBeaten,
            'all' => PlayerStatRankingKind::AllBeaten,
            default => PlayerStatRankingKind::RetailBeaten,
        };
    }

    /**
     * Fetch the paginated leaderboard data from the pre-computed rankings table.
     */
    private function getLeaderboardData(
        Request $request,
        int $currentPage,
        string $leaderboardKind,
        ?int $targetSystemId = null,
    ): mixed {
        $dbKind = $this->mapFilterKindToDbKind($leaderboardKind);

        $query = PlayerStatRanking::query()->where('kind', $dbKind);

        // Handle system filtering. A targetSystemId of 0 means all systems.
        if ($targetSystemId === 0 || $targetSystemId === null) {
            $query->whereNull('system_id');
        } else {
            $query->where('system_id', $targetSystemId);
        }

        $paginator = $query
            ->orderBy('row_number')
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

    /**
     * Looks up the user's ranking from the pre-computed rankings table.
     */
    private function getUserRankingData(
        int $userId,
        string $leaderboardKind,
        ?int $targetSystemId = null,
    ): ?array {
        $dbKind = $this->mapFilterKindToDbKind($leaderboardKind);

        $query = PlayerStatRanking::query()
            ->where('user_id', $userId)
            ->where('kind', $dbKind);

        // Handle system filtering. A targetSystemId of 0 means all systems.
        if ($targetSystemId === 0 || $targetSystemId === null) {
            $query->whereNull('system_id');
        } else {
            $query->where('system_id', $targetSystemId);
        }

        $userData = $query->first();

        if (!$userData) {
            return null;
        }

        // Attach metadata.
        $userDataWithMetadata = collect([$userData]);
        $this->attachRankingRowsMetadata($userDataWithMetadata);
        $userDataWithMetadata = $userDataWithMetadata->first();

        // Calculate the user's page number based on row number.
        $userPageNumber = (int) ceil($userData->row_number / $this->pageSize);

        return [
            'userRankingData' => $userDataWithMetadata,
            'userRank' => $userData->rank_number,
            'userPageNumber' => $userPageNumber,
        ];
    }
}
