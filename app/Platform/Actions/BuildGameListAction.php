<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Community\Enums\UserGameListType;
use App\Data\PaginatedData;
use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Concerns\BuildsGameListQueries;
use App\Platform\Data\GameData;
use App\Platform\Data\GameListEntryData;
use App\Platform\Data\PlayerGameData;
use App\Platform\Enums\GameListType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class BuildGameListAction
{
    use BuildsGameListQueries;

    public function execute(
        GameListType $listType,
        ?User $user,
        int $perPage = 25,
        int $page = 1,
        array $sort = [],
        array $filters = [],
        ?int $targetId = null,
    ): PaginatedData {
        /**
         * 👉 Game lists, by design, have a lot of complexity and are tricky to maintain.
         *    Try to keep the implementation details in execute() thin.
         *    Try to extract new logic to a method. This makes the action easier to test.
         *    Add tests for ALL new logic added in here. @see BuildGameListActionTest.php
         */

        // Regardless of the list context, we'll build a common base query which can use
        // the reusable sorts and filters and then be passed to a datatable component.
        $query = $this->buildBaseQuery($listType, $user, $targetId);

        // Clone the common base query to calculate the unfiltered total.
        // This lets us show something like "3 of 587 games" in the UI.
        $unfilteredTotal = $this->getUnfilteredResultsCount($query, $filters);

        // After building the base query, tack on whatever filters and sort we need.
        // We support multiple filters, but only a single selected sort.
        $this->applyFilters($query, $filters, $user);
        $this->applySorting($query, $sort, $user);

        // The base query may not respect ->distinct() operations done by some filters.
        // We'll override its `total` value with the correct one.
        $total = $query->count('GameData.ID');

        // Automatically adjust the current page if it exceeds the last page.
        $page = $this->ensurePageWithinBounds(
            total: $total,
            page: $page,
            perPage: $perPage,
        );

        /** @var LengthAwarePaginator<Game> $entries */
        $entries = $query->paginate($perPage, ['*'], 'page', $page);

        // If the user is authenticated, pull all their progress records for the games in the list.
        // Otherwise, call collect(), which is basically a noop.
        $playerGames = $user
            ? $this->getPlayerGames($user, $entries->pluck('id'))
            : collect();

        // If the user is authenticated, pull all their backlog records for the games in the list.
        // Otherwise, call collect(), which is basically a noop.
        // We also skip this query if the user is viewing their Want to Play Games List.
        // In that case, we optimistically assume every game viewed is a "backlog game".
        $backlogGames = collect();
        if ($listType !== GameListType::UserPlay && $user) {
            $backlogGames = $this->getBacklogGames($user, $entries->pluck('id'));
        }

        $transformedEntries = $entries->getCollection()->map(function (Game $game) use (
            $listType,
            $user,
            $playerGames,
            $backlogGames
        ): GameListEntryData {
            $playerGame = $playerGames->get($game->id);

            return new GameListEntryData(
                game: GameData::from($game)->include(
                    'achievementsPublished',
                    'badgeUrl',
                    'claimants',
                    'hasActiveOrInReviewClaims',
                    'lastUpdated',
                    'numVisibleLeaderboards',
                    'playersTotal',
                    'pointsTotal',
                    'pointsWeighted',
                    'releasedAt',
                    'releasedAtGranularity',
                    'system.iconUrl',
                    'system.nameShort',
                    $user?->can('develop') ? 'numUnresolvedTickets' : '',
                ),
                playerGame: $playerGame
                    ? PlayerGameData::fromPlayerGame($playerGame)->include('highestAward')
                    : null,
                isInBacklog: $listType === GameListType::UserPlay
                    ? true
                    : $backlogGames->has($game->id),
            );
        })->all(); // Convert the collection into an array.

        return PaginatedData::fromLengthAwarePaginator(
            $entries,
            total: $total,
            unfilteredTotal: $unfilteredTotal,
            items: $transformedEntries,
        );
    }

    /**
     * If the user provides a query param like ?page[number]=8 when there are
     * only 5 pages, set the current page to 5.
     */
    private function ensurePageWithinBounds(int $total, int $page, int $perPage): int
    {
        // Automatically adjust the current page if it exceeds the last page.
        $lastPage = (int) ceil($total / $perPage);
        if ($page > $lastPage) {
            $page = $lastPage;
        }

        return $page;
    }

    /**
     * @param Collection<int|string, mixed> $gameIds
     * @return Collection<int|string, int|string>
     */
    private function getBacklogGames(User $user, Collection $gameIds): Collection
    {
        return $user->gameListEntries()
            ->whereIn('GameID', $gameIds)
            ->where('type', UserGameListType::Play)
            ->pluck('GameID')
            ->flip(); // We flip the pluck results to use game IDs as keys for faster lookup.
    }

    /**
     * @param Collection<int|string, mixed> $gameIds
     * @return Collection<int, PlayerGame>
     */
    private function getPlayerGames(User $user, Collection $gameIds): Collection
    {
        return $user->playerGames()
            ->whereIn('game_id', $gameIds)
            ->with(['badges' => function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->whereIn('AwardType', [AwardType::GameBeaten, AwardType::Mastery]);
            }])
            ->get()
            ->keyBy('game_id');
    }

    /**
     * Clone the common base query to calculate the unfiltered total.
     * This lets us show something like "3 of 587 games" in the UI.
     *
     * @param Builder<Game> $query
     */
    private function getUnfilteredResultsCount(Builder $query, array $filters): ?int
    {
        $unfilteredTotal = null;
        if (!empty($filters)) {
            $unfilteredTotal = (clone $query)->count('GameData.ID');
        }

        return $unfilteredTotal;
    }
}
