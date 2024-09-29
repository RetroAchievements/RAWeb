<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Community\Enums\TicketState;
use App\Community\Enums\UserGameListType;
use App\Data\PaginatedData;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\PlayerGame;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Data\GameData;
use App\Platform\Data\GameListEntryData;
use App\Platform\Data\PlayerGameData;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\GameListType;
use App\Platform\Enums\UnlockMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BuildGameListAction
{
    public function execute(
        GameListType $listType,
        ?User $user,
        int $perPage = 25,
        int $page = 1,
        array $sort = [],
        array $filters = [],
    ): PaginatedData {
        /**
         * 👉 Game lists, by design, have a lot of complexity and are tricky to maintain.
         *    Try to keep the implementation details in execute() thin.
         *    Try to extract new logic to a method. This makes the action easier to test.
         *    Add tests for ALL new logic added in here. @see BuildGameListActionTest.php
         */

        // Regardless of the list context, we'll build a common base query which can use
        // the reusable sorts and filters and then be passed to a datatable component.
        $query = $this->buildBaseQuery($listType, $user);

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

        $transformedEntries = $entries
            ->getCollection()
            ->map(function (Game $game) use (
                $listType,
                $user,
                $playerGames,
                $backlogGames
            ): GameListEntryData {
                $playerGame = $playerGames->get($game->id);

                return new GameListEntryData(
                    game: GameData::from($game)->include(
                        'system.nameShort',
                        'system.iconUrl',
                        'achievementsPublished',
                        'badgeUrl',
                        'pointsTotal',
                        'pointsWeighted',
                        'releasedAt',
                        'releasedAtGranularity',
                        'playersTotal',
                        'lastUpdated',
                        'numVisibleLeaderboards',
                        $user?->can('develop') ? 'numUnresolvedTickets' : '',
                    ),
                    playerGame: $playerGame
                        ? PlayerGameData::fromPlayerGame($playerGame)->include('highestAward')
                        : null,
                    isInBacklog: $listType === GameListType::UserPlay
                        ? true
                        : $backlogGames->has($game->id),
                );
            });
        $entries->setCollection($transformedEntries);

        return PaginatedData::fromLengthAwarePaginator(
            $entries,
            total: $total,
            unfilteredTotal: $unfilteredTotal,
        );
    }

    /**
     * @return Builder<Game>
     */
    private function buildBaseQuery(GameListType $listType, ?User $user = null): Builder
    {
        $query = Game::query()
            ->with(['system'])
            ->withLastAchievementUpdate()
            ->addSelect(['GameData.*'])
            ->addSelect([
                // Fetch counts here to avoid N+1 query problems.

                'num_visible_leaderboards' => Leaderboard::selectRaw('COUNT(*)')
                    ->whereColumn('LeaderboardDef.GameID', 'GameData.ID')
                    ->where('LeaderboardDef.DisplayOrder', '>=', 0),
            ]);

        // Only attempt to fetch the "Open Tickets" column counts if the user
        // is a dev. Otherwise, skip it.
        if ($user?->can('develop')) {
            $query->addSelect([
                'num_unresolved_tickets' => Ticket::selectRaw('COUNT(*)')
                    ->join('Achievements', 'Ticket.AchievementID', '=', 'Achievements.ID')
                    ->whereColumn('Achievements.GameID', 'GameData.ID')
                    ->where('Achievements.Flags', AchievementFlag::OfficialCore)
                    ->whereIn('Ticket.ReportState', [TicketState::Open, TicketState::Request]),
            ]);
        }

        switch ($listType) {
            case GameListType::UserPlay:
                $query->whereHas('gameListEntries', function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->where('type', UserGameListType::Play);
                });
                break;

            // TODO implement these other use cases
            case GameListType::UserDevelop:
            case GameListType::AllGames:
            case GameListType::System:
            case GameListType::Hub:
            case GameListType::DeveloperSets:
                throw new InvalidArgumentException("List type not implemented");
            default:
                throw new InvalidArgumentException("Invalid list type");
        }

        return $query;
    }

    /**
     * @param Builder<Game> $query
     */
    private function applyFilters(Builder $query, array $filters, ?User $user = null): void
    {
        foreach ($filters as $filterKey => $filterValues) {
            switch ($filterKey) {
                /*
                 * only show games matching a specific game title pattern
                 */
                case 'title':
                    if (!empty($filterValues[0])) {
                        $query->where('GameData.Title', 'LIKE', '%' . $filterValues[0] . '%');
                    }
                    break;

                /*
                 * only show games matching a specific list of system IDs
                 */
                case 'system':
                    $query->whereHas('system', function (Builder $query) use ($filterValues) {
                        $query->whereIn('ID', $filterValues);
                    });
                    break;

                /*
                 * only show games matching a specific list of user earned awards
                 */
                case 'award':
                    $this->applyAwardFilter($query, $filterValues, $user);
                    break;

                /*
                 * only show games based on whether they have achievements published
                 */
                case 'achievementsPublished':
                    $this->applyAchievementsPublishedFilter($query, $filterValues);
                    break;

                default:
                    break;
            }
        }
    }

    /**
     * ["field" => "system", "direction" => "desc"]
     *
     * @param Builder<Game> $query
     */
    private function applySorting(Builder $query, array $sort, ?User $user = null): void
    {
        $validSortFields = [
            'title',
            'system',
            'achievementsPublished',
            'pointsTotal',
            'retroRatio',
            'lastUpdated',
            'releasedAt',
            'playersTotal',
            'numVisibleLeaderboards',
            'numUnresolvedTickets',
            'progress',
        ];

        if (isset($sort['field']) && in_array($sort['field'], $validSortFields)) {
            $sortDirection = $sort['direction'] ?? 'asc';

            switch ($sort['field']) {
                /*
                 * game title, with tagged games placed at the bottom of the list
                 */
                case 'title':
                    $this->applyGameTitleSorting($query, $sortDirection);
                    break;

                /*
                 * game system name, by name_short (eg: "A2600", not "Atari 2600")
                 */
                case 'system':
                    $query
                        ->join('Console', 'GameData.ConsoleID', '=', 'Console.ID')
                        ->orderBy('Console.name_short', $sortDirection);
                    break;

                /*
                 * count of official achievements associated with the game's core set
                 */
                case 'achievementsPublished':
                    $query->orderBy('GameData.achievements_published', $sortDirection);
                    break;

                /*
                 * count of points from core/official achievements associated with the game's core set
                 */
                case 'pointsTotal':
                    $query->orderBy('GameData.points_total', $sortDirection);
                    break;

                /*
                 * points_weighted / points_total from core/official achievements
                 * associated with the game's core set
                 */
                case 'retroRatio':
                    $query
                        ->selectRaw(
                            "CASE
                                WHEN GameData.points_total = 0 THEN 0
                                ELSE GameData.TotalTruePoints / GameData.points_total
                            END AS retro_ratio"
                        )
                        ->orderBy('retro_ratio', $sortDirection);
                    break;

                /*
                 * when an update was last made to the game's achievement logic
                 * TODO use updates from the triggers table, achievement logic changes is what players care about
                 *      and DateModified includes when other stuff changed like titles, descriptions, etc
                 */
                case 'lastUpdated':
                    $query
                        ->selectRaw(
                            "COALESCE(
                                (SELECT MAX(DateModified) FROM Achievements WHERE Achievements.GameID = GameData.ID),
                                GameData.Updated
                            ) AS last_updated"
                        )
                        ->orderBy('last_updated', $sortDirection);
                    break;

                /*
                 * the game's earliest release date
                 */
                case 'releasedAt':
                    $this->applyReleasedAtSorting($query, $sortDirection);
                    break;

                /*
                 * count of all players (softcore and hardcore) for the game
                 */
                case 'playersTotal':
                    $query->orderBy('GameData.players_total', $sortDirection);
                    break;

                /*
                 * the game's count of non-hidden leaderboards (order_column >= 0)
                 */
                case 'numVisibleLeaderboards':
                    $query->orderBy('num_visible_leaderboards', $sortDirection);
                    break;

                /*
                 * the game's count of tickets awaiting resolution
                 */
                case 'numUnresolvedTickets':
                    $query->orderBy('num_unresolved_tickets', $sortDirection);
                    break;

                /*
                 * the user's progress, ordered by # of achievements earned, on the game
                 */
                case 'progress':
                    $this->applyProgressSorting($query, $sortDirection, $user);
                    break;

                /*
                 * if we have no idea what the user is trying to sort by, fall back to sorting by title
                 */
                default:
                    $this->applyGameTitleSorting($query, $sortDirection);
            }
        }

        // Default to sorting by title if no valid sort field is provided.
        // Otherwise, always secondary sort by title.
        $this->applyGameTitleSorting($query);
    }

    /**
     * Ensure games on the list are sorted properly.
     * For titles starting with "~", the sort order is determined by the content
     * within the "~" markers followed by the content after the "~". This ensures
     * that titles with "~" are grouped together and sorted alphabetically based
     * on their designated categories and then by their actual game title.
     *
     * The "~" prefix is retained in the SortTitle of games with "~" to ensure these
     * games are sorted at the end of the list, maintaining a clear separation from
     * non-prefixed titles. This approach allows game titles to be grouped and sorted
     * in a specific order:
     *
     * 1. Non-prefixed titles are sorted alphabetically at the beginning of the list.
     * 2. Titles prefixed with "~" are grouped at the end, sorted first by the category
     *    specified within the "~" markers, and then alphabetically by the title following
     *    the "~".
     *
     * @param Builder<Game> $query
     */
    private function applyGameTitleSorting(Builder $query, string $sortDirection = 'asc'): void
    {
        // We're extra careful here to use functions supported by both MariaDB
        // and SQLite. This is preferable to altering the query specifically for
        // SQLite, because if we do so then we can't actually trust any test results.
        $query
            ->selectRaw(
                "GameData.*,
                CASE
                    WHEN GameData.Title LIKE '~%' THEN 1
                    ELSE 0
                END AS SortPrefix,
                CASE 
                    WHEN GameData.Title LIKE '~%' THEN
                        '~' || SUBSTR(GameData.Title, 2, INSTR(SUBSTR(GameData.Title, 2), '~') - 1) || ' ' || TRIM(SUBSTR(GameData.Title, INSTR(GameData.Title, '~') + 1))
                    ELSE GameData.Title
                END AS SortTitle"
            )
            ->orderByRaw('SortPrefix ' . $sortDirection)
            ->orderByRaw('SortTitle ' . $sortDirection);
    }

    /**
     * Sort games on the list by their release dates.
     * The released_at_granularity value must be taken into account when sorting.
     * This column indicates the level of precision available for the release date
     * (eg: year, month, or exact day).
     *
     * The sorting logic works as follows:
     *
     * 1. If released_at_granularity is set to "year", the release date is normalized
     *    to the first day of that year (eg: "1985-01-01").
     * 2. If released_at_granularity is set to "month", the release date is normalized
     *    to the first day of that month (eg: "1985-05-01").
     * 3. If no granularity is set, or the granularity is "day", the release date is used as-is.
     *
     * This ensures that games with less precise release dates are sorted logically while
     * maintaining the correct order relative to their peers. Games are then ordered by their
     * normalized release date, either ascending or descending.
     *
     * @param Builder<Game> $query
     */
    private function applyReleasedAtSorting(Builder $query, string $sortDirection = 'asc'): void
    {
        // We're extra careful here to use functions supported by both MariaDB
        // and SQLite. This is preferable to altering the query specifically for
        // SQLite, because if we do so then we can't actually trust any test results.
        $query
            ->selectRaw(
                "GameData.*,
                CASE
                    WHEN GameData.released_at_granularity = 'year' THEN
                        DATE(CONCAT(SUBSTR(GameData.released_at, 1, 4), '-01-01'))
                    WHEN GameData.released_at_granularity = 'month' THEN
                        DATE(CONCAT(SUBSTR(GameData.released_at, 1, 7), '-01'))
                    ELSE
                        COALESCE(GameData.released_at, '9999-12-31')
                END AS normalized_released_at"
            )
            ->orderBy('normalized_released_at', $sortDirection);
    }

    /**
     * Sort games on the list by the player's progress percentage.
     * The player's progress percentage is measured by the number of achievements
     * they've earned divided by the number of achievements published on the game.
     * If the user has no progress data, their progress is treated as 0.
     *
     * If two games have the same progress percentage, a secondary sort is applied
     * by the number of achievements published.
     *
     * @param Builder<Game> $query
     */
    private function applyProgressSorting(Builder $query, string $sortDirection = 'asc', ?User $user = null): void
    {
        // If there's no user, then we have no progress to sort by. Bail.
        if (!$user) {
            $this->applyGameTitleSorting($query, $sortDirection);

            return;
        }

        $query
            ->leftJoin('player_games', function ($join) use ($user) {
                $join->on('player_games.game_id', '=', 'GameData.ID')
                    ->where('player_games.user_id', '=', $user->id);
            })
            ->selectRaw("
                CASE
                    WHEN player_games.completion_percentage IS NULL THEN 0
                    ELSE player_games.completion_percentage
                END AS progress_percentage
            ")
            ->orderBy('progress_percentage', $sortDirection)
            ->orderBy('GameData.achievements_published', $sortDirection);
    }

    /**
     * Filters games based on whether they have any achievements published.
     * Possible values are "has", "none", or "either".
     *
     * If multiple values are given, only the first one is considered.
     *
     * @param Builder<Game> $query
     */
    private function applyAchievementsPublishedFilter(Builder $query, array $filterValues): void
    {
        // Bail early if necessary. If the user gives both options, it's the "either" case.
        if (empty($filterValues) || count($filterValues) === 2) {
            return;
        }

        // $filterValues is an array, but we only consider a single value.
        $value = $filterValues[0];

        switch ($value) {
            case 'has':
                $query->where('GameData.achievements_published', '>', 0);
                break;

            case 'none':
                $query->where('GameData.achievements_published', 0);
                break;

            case 'either':
            default:
                break;
        }
    }

    /**
     * Filter games based on the player's award status.
     * The player's awards can represent various levels of completion for each game.
     * The supported awards are beaten (softcore), beaten, completed, and mastered.
     *
     * The filtering logic works as follows:
     *
     * 1. "unfinished": Filters games where the player has no award of any kind for the game.
     * 2. "finished": Filters games where the player has any award for the game.
     * 3. "beaten_softcore": Filters games where the player has a softcore beaten award.
     * 4. "beaten_hardcore": Filters games where the player has a hardcore beaten award.
     * 5. "completed": Filters games where the player has a softcore mastery award.
     * 6. "mastered": Filters games where the player has a hardcore mastery award.
     *
     * The SiteAwards.AwardDataExtra field is used to differentiate between softcore (0) and hardcore (1).
     *
     * If the user is not provided (ie: they aren't logged in), no filtering will be performed.
     *
     * @param Builder<Game> $query
     */
    private function applyAwardFilter(Builder $query, array $awardValues, ?User $user = null): void
    {
        // If there's no user, then we have no awards to filter by. Bail.
        if (!$user) {
            return;
        }

        /**
         * We'll pull this data from SiteAwards. Similar data does exist on
         * player_games, but it is not static. In other words, on player_games,
         * we revoke the "100% completion" when a set gets revised. This is not
         * how we communicate the mastery awards UX to players, so we reach for
         * SiteAwards data instead.
         */
        $query->where(function ($query) use ($awardValues, $user) {
            foreach ($awardValues as $award) {
                switch ($award) {
                    /*
                     * games where the player has no award of any kind for the game
                     */
                    case 'unfinished':
                        $query->orWhereNotExists(function ($subQuery) use ($user) {
                            $subQuery->select(DB::raw(1))
                                ->from('SiteAwards')
                                ->whereColumn('SiteAwards.AwardData', 'GameData.ID')
                                ->where('SiteAwards.user_id', $user->id)
                                ->whereIn('SiteAwards.AwardType', [AwardType::GameBeaten, AwardType::Mastery]);
                        });
                        break;

                    /*
                     * games where the player has any award for the game
                     */
                    case 'finished':
                        $query->orWhereExists(function ($subQuery) use ($user) {
                            $subQuery->select(DB::raw(1))
                                ->from('SiteAwards')
                                ->whereColumn('SiteAwards.AwardData', 'GameData.ID')
                                ->where('SiteAwards.user_id', $user->id)
                                ->whereIn('SiteAwards.AwardType', [AwardType::GameBeaten, AwardType::Mastery]);
                        });
                        break;

                    /*
                     * games where the player has a softcore beaten award
                     */
                    case 'beaten_softcore':
                        $query->orWhereExists(function ($subQuery) use ($user) {
                            $subQuery->select(DB::raw(1))
                                ->from('SiteAwards')
                                ->whereColumn('SiteAwards.AwardData', 'GameData.ID')
                                ->where('SiteAwards.user_id', $user->id)
                                ->where('SiteAwards.AwardType', AwardType::GameBeaten)
                                ->where('SiteAwards.AwardDataExtra', UnlockMode::Softcore)
                                ->whereNotExists(function ($excludeSubQuery) use ($user) {
                                    $excludeSubQuery->select(DB::raw(1))
                                        ->from('SiteAwards as sa2')
                                        ->whereColumn('sa2.AwardData', 'GameData.ID')
                                        ->where('sa2.user_id', $user->id)
                                        ->where('sa2.AwardType', AwardType::Mastery);
                                });
                        });
                        break;

                    /*
                     * games where the player has a softcore beaten award
                     */
                    case 'beaten_hardcore':
                        $query->orWhereExists(function ($subQuery) use ($user) {
                            $subQuery->select(DB::raw(1))
                                ->from('SiteAwards')
                                ->whereColumn('SiteAwards.AwardData', 'GameData.ID')
                                ->where('SiteAwards.user_id', $user->id)
                                ->where('SiteAwards.AwardType', AwardType::GameBeaten)
                                ->where('SiteAwards.AwardDataExtra', UnlockMode::Hardcore)
                                ->whereNotExists(function ($excludeSubQuery) use ($user) {
                                    $excludeSubQuery->select(DB::raw(1))
                                        ->from('SiteAwards as sa2')
                                        ->whereColumn('sa2.AwardData', 'GameData.ID')
                                        ->where('sa2.user_id', $user->id)
                                        ->where('sa2.AwardType', AwardType::Mastery);
                                });
                        });
                        break;

                    /*
                     * games where the player has a softcore mastery award
                     */
                    case 'completed':
                        $query->orWhereExists(function ($subQuery) use ($user) {
                            $subQuery->select(DB::raw(1))
                                ->from('SiteAwards')
                                ->whereColumn('SiteAwards.AwardData', 'GameData.ID')
                                ->where('SiteAwards.user_id', $user->id)
                                ->where('SiteAwards.AwardType', AwardType::Mastery)
                                ->where('SiteAwards.AwardDataExtra', UnlockMode::Softcore)
                                ->whereNotExists(function ($excludeSubQuery) use ($user) {
                                    $excludeSubQuery->select(DB::raw(1))
                                        ->from('SiteAwards as sa2')
                                        ->whereColumn('sa2.AwardData', 'GameData.ID')
                                        ->where('sa2.user_id', $user->id)
                                        ->where('sa2.AwardType', AwardType::Mastery)
                                        ->where('sa2.AwardDataExtra', UnlockMode::Hardcore);
                                });
                        });
                        break;

                    /*
                     * games where the player has a hardcore mastery award
                     */
                    case 'mastered':
                        $query->orWhereExists(function ($subQuery) use ($user) {
                            $subQuery->select(DB::raw(1))
                                ->from('SiteAwards')
                                ->whereColumn('SiteAwards.AwardData', 'GameData.ID')
                                ->where('SiteAwards.user_id', $user->id)
                                ->where('SiteAwards.AwardType', AwardType::Mastery)
                                ->where('SiteAwards.AwardDataExtra', UnlockMode::Hardcore);
                        });
                        break;

                    default:
                        break;
                }
            }
        });
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
