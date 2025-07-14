<?php

declare(strict_types=1);

namespace App\Platform\Concerns;

use App\Community\Enums\AwardType;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\TicketState;
use App\Community\Enums\UserGameListType;
use App\Models\AchievementSetClaim;
use App\Models\Game;
use App\Models\Leaderboard;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserGameListEntry;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\GameListProgressFilterValue;
use App\Platform\Enums\GameListSetTypeFilterValue;
use App\Platform\Enums\GameListSortField;
use App\Platform\Enums\GameListType;
use App\Platform\Enums\UnlockMode;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

trait BuildsGameListQueries
{
    /**
     * @return Builder<Game>
     */
    private function buildBaseQuery(
        GameListType $listType,
        ?User $user = null,
        ?int $targetId = null,
    ): Builder {
        $query = Game::query()
            ->with([
                'system',
                'achievementSetClaims' => function ($query) {
                    $query->activeOrInReview()->with(['user' => function ($query) {
                        // Only select the fields we need for the UserData DTO.
                        $query->select(['ID', 'User', 'display_name', 'Permissions']);
                    }]);
                },
            ])
            ->withLastAchievementUpdate()
            ->addSelect(['GameData.*'])
            ->addSelect([
                // Fetch counts here to avoid N+1 query problems.

                'has_active_or_in_review_claims' => AchievementSetClaim::selectRaw('CASE WHEN COUNT(*) > 0 THEN 1 ELSE 0 END')
                    ->whereColumn('SetClaim.game_id', 'GameData.ID')
                    ->whereIn('Status', [ClaimStatus::Active, ClaimStatus::InReview])
                    ->limit(1),

                'num_visible_leaderboards' => Leaderboard::selectRaw('COUNT(*)')
                    ->whereColumn('LeaderboardDef.GameID', 'GameData.ID')
                    ->where('LeaderboardDef.DisplayOrder', '>=', 0),
            ]);

        // Only attempt to fetch the "Requests" column counts if we're on
        // the Most Requested Sets datatable. Otherwise, skip it.
        if ($listType === GameListType::SetRequests) {
            $query->addSelect([
                'num_requests' => UserGameListEntry::selectRaw('COUNT(*)')
                    ->whereColumn('SetRequest.GameID', 'GameData.ID')
                    ->where('SetRequest.type', UserGameListType::AchievementSetRequest),
            ]);
        }

        // Only attempt to fetch the "Open Tickets" column counts if the user
        // is a dev. Otherwise, skip it.
        if ($user?->can('develop')) {
            $query->addSelect([
                'num_unresolved_tickets' => Ticket::selectRaw('COUNT(*)')
                    ->join('Achievements', 'Ticket.AchievementID', '=', 'Achievements.ID')
                    ->whereColumn('Achievements.GameID', 'GameData.ID')
                    ->where('Achievements.Flags', AchievementFlag::OfficialCore->value)
                    ->whereIn('Ticket.ReportState', [TicketState::Open, TicketState::Request]),
            ]);
        }

        switch ($listType) {
            case GameListType::AllGames:
                // Exclude non game systems, inactive systems, and subsets.
                $validSystemIds = System::active()
                    ->gameSystems()
                    ->pluck('ID')
                    ->all();

                $query->whereIn('GameData.ConsoleID', $validSystemIds);
                break;

            case GameListType::SetRequests:
                // Only show games with at least 1 request and 0 achievements published.
                // We also don't care if the system is active or not.
                $validSystemIds = System::gameSystems()
                    ->pluck('ID')
                    ->all();

                $query->whereIn('GameData.ConsoleID', $validSystemIds)
                    ->where('GameData.achievements_published', 0)
                    ->whereExists(function ($subquery) {
                        $subquery->select(DB::raw(1))
                            ->from('SetRequest')
                            ->whereColumn('SetRequest.GameID', 'GameData.ID')
                            ->where('SetRequest.type', UserGameListType::AchievementSetRequest);
                    });
                break;

            case GameListType::Hub:
                $query
                    ->join('game_set_games', 'GameData.ID', '=', 'game_set_games.game_id')
                    ->join('game_sets', 'game_sets.id', 'game_set_games.game_set_id')
                    ->whereNull('game_sets.deleted_at')
                    ->where('game_sets.id', $targetId)
                    ->where('GameData.ConsoleID', '!=', System::Hubs);
                break;

            case GameListType::System:
                $query
                    ->where('GameData.ConsoleID', $targetId);
                    // TODO we need some kind of special visual treatment for subsets on the game lists
                    // ->where('GameData.Title', 'not like', "%[Subset -%");
                break;

            case GameListType::UserPlay:
                $query->whereHas('gameListEntries', function ($query) use ($user) {
                    $query->where('user_id', $user->id)
                          ->where('type', UserGameListType::Play);
                });
                break;

            case GameListType::UserSpecificSuggestions:
            case GameListType::GameSpecificSuggestions:
                if (!isset($this->suggestions)) {
                    throw new InvalidArgumentException("Suggestions must be generated before building the base query.");
                }

                $gameIds = array_map(fn ($suggestion) => $suggestion->gameId, $this->suggestions);
                $query->whereIn('GameData.ID', $gameIds);
                break;

            // TODO implement these other use cases
            case GameListType::UserDevelop:
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
            /*
             * only show games matching a specific game title pattern
             */
            if ($filterKey === 'title' && !empty($filterValues[0])) {
                $query->where('GameData.Title', 'LIKE', '%' . $filterValues[0] . '%');
                continue;
            }

            /*
             * only show games matching a specific list of system IDs
             */
            if ($filterKey === 'system') {
                $systemIds = in_array('supported', $filterValues)
                    ? System::active()->gameSystems()->pluck('ID')->all()
                    : $filterValues;
                $query->whereIn('GameData.ConsoleID', $systemIds);
                continue;
            }

            /*
             * only show games based on their tags
             */
            if ($filterKey === 'game-type' && !empty($filterValues)) {
                $this->applyGameTypeFilter($query, $filterValues);
                continue;
            }

            /*
             * only show games based on whether they are "subset games"
             */
            if ($filterKey === 'subsets') {
                if ($filterValues[0] === GameListSetTypeFilterValue::OnlyGames->value) {
                    $query->where('GameData.Title', 'not like', '%[Subset -%');
                }
                if ($filterValues[0] === GameListSetTypeFilterValue::OnlySubsets->value) {
                    $query->where('GameData.Title', 'like', '%[Subset -%');
                }
                continue;
            }

            /*
             * only show games matching a specific category of the current user's progress
             */
            if ($filterKey === 'progress') {
                $this->applyProgressFilter($query, $filterValues[0], $user);
                continue;
            }

            /*
             * only show games based on whether they have achievements published
             */
            if ($filterKey === 'achievementsPublished') {
                $this->applyAchievementsPublishedFilter($query, $filterValues);
                continue;
            }

            /*
             * only show games based on whether they have active claims
             */
            if ($filterKey === 'hasActiveOrInReviewClaims') {
                if (!empty($filterValues) && $filterValues[0] !== 'any') {
                    if ($filterValues[0] === 'claimed') {
                        $query->whereExists(function ($subquery) {
                            $subquery->select(DB::raw(1))
                                ->from('SetClaim')
                                ->whereColumn('SetClaim.game_id', 'GameData.ID')
                                ->whereIn('Status', [ClaimStatus::Active, ClaimStatus::InReview]);
                        });
                    } elseif ($filterValues[0] === 'unclaimed') {
                        $query->whereNotExists(function ($subquery) {
                            $subquery->select(DB::raw(1))
                                ->from('SetClaim')
                                ->whereColumn('SetClaim.game_id', 'GameData.ID')
                                ->whereIn('Status', [ClaimStatus::Active, ClaimStatus::InReview]);
                        });
                    }
                }
                continue;
            }

            /*
             * only show games requested by a specific user
             */
            if ($filterKey === 'user' && !empty($filterValues[0])) {
                $query->whereExists(function ($subquery) use ($filterValues) {
                    $subquery->select(DB::raw(1))
                        ->from('SetRequest')
                        ->join('UserAccounts', 'UserAccounts.ID', '=', 'SetRequest.user_id')
                        ->whereColumn('SetRequest.GameID', 'GameData.ID')
                        ->where('SetRequest.type', UserGameListType::AchievementSetRequest)
                        ->where('UserAccounts.display_name', $filterValues[0]);
                });
                continue;
            }
        }
    }

    /**
     * @param Builder<Game> $query
     * @param array{field: string, direction: 'asc'|'desc'} $sort
     */
    private function applySorting(Builder $query, array $sort, ?User $user = null): void
    {
        if (isset($sort['field']) && GameListSortField::tryFrom($sort['field'])) {
            $sortDirection = $sort['direction'] ?? 'asc';

            switch ($sort['field']) {
                /*
                 * game title, with tagged games placed at the bottom of the list
                 */
                case GameListSortField::Title->value:
                    $query->orderBy('GameData.sort_title', $sortDirection);
                    break;

                /*
                 * game system name, by name_short (eg: "A2600", not "Atari 2600")
                 */
                case GameListSortField::System->value:
                    $query
                        ->join('Console', 'GameData.ConsoleID', '=', 'Console.ID')
                        ->orderBy('Console.name_short', $sortDirection);
                    break;

                /*
                 * count of official achievements associated with the game's core set
                 */
                case GameListSortField::AchievementsPublished->value:
                    $query->orderBy('GameData.achievements_published', $sortDirection);
                    break;

                /*
                 * whether or not there are any active or in review claims associated with the game
                 */
                case GameListSortField::HasActiveOrInReviewClaims->value:
                    $query->orderBy('has_active_or_in_review_claims', $sortDirection);
                    break;

                /*
                 * count of points from core/official achievements associated with the game's core set
                 */
                case GameListSortField::PointsTotal->value:
                    $query->orderBy('GameData.points_total', $sortDirection);
                    break;

                /*
                 * points_weighted / points_total from core/official achievements
                 * associated with the game's core set
                 */
                case GameListSortField::RetroRatio->value:
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
                case GameListSortField::LastUpdated->value:
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
                case GameListSortField::ReleasedAt->value:
                    $this->applyReleasedAtSorting($query, $sortDirection);
                    break;

                /*
                 * count of all players (softcore and hardcore) for the game
                 */
                case GameListSortField::PlayersTotal->value:
                    $query->orderBy('GameData.players_total', $sortDirection);
                    break;

                /*
                 * the game's count of non-hidden leaderboards (order_column >= 0)
                 */
                case GameListSortField::NumVisibleLeaderboards->value:
                    $query->orderBy('num_visible_leaderboards', $sortDirection);
                    break;

                /*
                 * the game's count of tickets awaiting resolution
                 */
                case GameListSortField::NumUnresolvedTickets->value:
                    if ($user?->can('develop')) {
                        $query->orderBy('num_unresolved_tickets', $sortDirection);
                    }
                    break;

                /*
                 * the game's count of set requests
                 */
                case GameListSortField::NumRequests->value:
                    $query->orderBy('num_requests', $sortDirection);
                    break;

                /*
                 * the user's progress, ordered by # of achievements earned, on the game
                 */
                case GameListSortField::Progress->value:
                    $this->applyProgressSorting($query, $sortDirection, $user);
                    break;

                /*
                 * if we have no idea what the user is trying to sort by, fall back to sorting by title
                 */
                default:
                    $query->orderBy('GameData.sort_title', $sortDirection);
                    break;
            }
        }

        // Default to sorting by title if no valid sort field is provided.
        // Otherwise, always secondary sort by title.
        $query->orderBy('GameData.sort_title', 'asc');
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
            ->selectRaw(<<<SQL
                GameData.*,
                CASE
                    WHEN GameData.released_at_granularity = 'year' THEN
                        DATE(CONCAT(SUBSTR(GameData.released_at, 1, 4), '-01-01'))
                    WHEN GameData.released_at_granularity = 'month' THEN
                        DATE(CONCAT(SUBSTR(GameData.released_at, 1, 7), '-01'))
                    ELSE
                        COALESCE(GameData.released_at, '9999-12-31')
                END AS normalized_released_at,
                CASE GameData.released_at_granularity
                    WHEN 'year' THEN 1
                    WHEN 'month' THEN 2
                    WHEN 'day' THEN 3
                    ELSE 4
                END AS granularity_order
            SQL)
            // Ensure NULL release dates always sort to the end, regardless of sort direction.
            ->orderByRaw('released_at IS NULL')
            ->orderBy('normalized_released_at', $sortDirection)
            ->orderBy('granularity_order', $sortDirection);
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
            $query->orderBy('GameData.sort_title', $sortDirection);

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
        // Bail early if necessary.
        if (empty($filterValues)) {
            return;
        }

        // $filterValues is an array, but we only consider a single value.
        $value = $filterValues[0];

        switch ($value) {
            case 'has':
                $query->where('GameData.achievements_published', '>', 0);
                break;

            case 'none':
                $query->where(function ($q) {
                    $q->where('GameData.achievements_published', 0)
                        ->orWhereNull('GameData.achievements_published');
                });
                break;

            case 'either':
            default:
                break;
        }
    }

    /**
     * Filters games based on the presence (or lack thereof) of tags.
     *
     * @param Builder<Game> $query
     */
    private function applyGameTypeFilter(Builder $query, array $filterValues): void
    {
        // Bail early if necessary.
        if (empty($filterValues)) {
            return;
        }

        // Split filters into "retail" and non-retail tag filters.
        $hasRetailFilter = in_array('retail', $filterValues);
        $tagFilters = array_filter($filterValues, fn ($value) => $value !== 'retail');

        if ($hasRetailFilter && empty($tagFilters)) {
            // If retail is the only filter, only return untagged games.
            $query->whereNotExists(function ($subquery) {
                $subquery->select(DB::raw(1))
                    ->from('tags')
                    ->join('taggables', 'tags.id', '=', 'taggables.tag_id')
                    ->whereColumn('taggables.taggable_id', 'GameData.ID')
                    ->where('taggables.taggable_type', 'game')
                    ->where('tags.type', 'game');
            });
        } elseif ($hasRetailFilter && !empty($tagFilters)) {
            // If both retail and other tags are requested, return both
            // untagged games and games with the specified tags.
            $query->where(function ($query) use ($tagFilters) {
                // Either the game has no tags...
                $query->whereNotExists(function ($subquery) {
                    $subquery->select(DB::raw(1))
                        ->from('tags')
                        ->join('taggables', 'tags.id', '=', 'taggables.tag_id')
                        ->whereColumn('taggables.taggable_id', 'GameData.ID')
                        ->where('taggables.taggable_type', 'game')
                        ->where('tags.type', 'game');
                })
                // ... or it has at least one of the specified tags.
                ->orWhere(function ($query) use ($tagFilters) {
                    $query->withAnyTags($tagFilters, 'game');
                });
            });
        } elseif (!empty($tagFilters)) {
            $query->withAnyTags($tagFilters, 'game');
        }
    }

    /**
     * Filter games based on the player's progress.
     *
     * The SiteAwards.AwardDataExtra field is used to differentiate between softcore (0) and hardcore (1).
     *
     * If the user is not provided (ie: they aren't logged in), no filtering will be performed.
     *
     * @param Builder<Game> $query
     */
    private function applyProgressFilter(Builder $query, string $filterValue, ?User $user = null): void
    {
        // If there's no user or the filter value is invalid, then we can't filter. Bail.
        if (!$user || !GameListProgressFilterValue::tryFrom($filterValue)) {
            return;
        }

        /**
         * We'll pull this data from SiteAwards. Similar data does exist on
         * player_games, but it is not static. In other words, on player_games,
         * we revoke the "100% completion" when a set gets revised. This is not
         * how we communicate the mastery awards UX to players, so we reach for
         * SiteAwards data instead.
         */
        $query->where(function ($query) use ($filterValue, $user) {
            switch ($filterValue) {
                /*
                 * games where the player has no achievements unlocked and no awards
                 */
                case GameListProgressFilterValue::Unstarted->value:
                    $query->orWhere(function ($subQuery) use ($user) {
                        $subQuery->whereNotExists(function ($q) use ($user) {
                            $this->baseProgressQuery($user)($q)
                                ->where('player_games.achievements_unlocked', '>', 0);
                        })
                        ->whereNotExists(function ($q) use ($user) {
                            $this->baseAwardsQuery($user)($q)
                                ->whereIn('SiteAwards.AwardType', [AwardType::GameBeaten, AwardType::Mastery]);
                        });
                    });
                    break;

                /*
                 * games where the player has no award of any kind for the game, but does have progress
                 */
                case GameListProgressFilterValue::Unfinished->value:
                    $query->orWhere(function ($subQuery) use ($user) {
                        // Must have at least one achievement unlocked...
                        $subQuery->whereExists(function ($q) use ($user) {
                            $this->baseProgressQuery($user)($q)
                                ->where('player_games.achievements_unlocked', '>', 0);
                        })
                        // ... but no game award.
                        ->whereNotExists(function ($q) use ($user) {
                            $this->baseAwardsQuery($user)($q)
                                ->whereIn('SiteAwards.AwardType', [AwardType::GameBeaten, AwardType::Mastery]);
                        });
                    });
                    break;

                /*
                 * games where the player has any award for the game
                 */
                case GameListProgressFilterValue::GteBeatenSoftcore->value:
                    $query->orWhereExists(function ($subQuery) use ($user) {
                        $this->baseAwardsQuery($user)($subQuery)
                            ->whereIn('SiteAwards.AwardType', [AwardType::GameBeaten, AwardType::Mastery]);
                    });
                    break;

                /*
                 * games where the player has a hardcore beaten award or better
                 * (includes beaten hardcore, mastered softcore, mastered hardcore)
                 */
                case GameListProgressFilterValue::GteBeatenHardcore->value:
                    $query->orWhereExists(function ($subQuery) use ($user) {
                        $this->baseAwardsQuery($user)($subQuery)
                            ->where(function ($q) {
                                $q->where(function ($q2) {
                                    $q2->where('SiteAwards.AwardType', AwardType::GameBeaten)
                                        ->where('SiteAwards.AwardDataExtra', UnlockMode::Hardcore);
                                })
                                ->orWhere('SiteAwards.AwardType', AwardType::Mastery);
                            });
                    });
                    break;

                /*
                 * games where the player has a softcore beaten award
                 */
                case GameListProgressFilterValue::EqBeatenSoftcore->value:
                    $query->orWhere(function ($subQuery) use ($user) {
                        $subQuery->whereExists(function ($q) use ($user) {
                            $this->baseAwardsQuery($user)($q)
                                ->where('SiteAwards.AwardType', AwardType::GameBeaten)
                                ->where('SiteAwards.AwardDataExtra', UnlockMode::Softcore);
                        })
                        ->whereNotExists(function ($q) use ($user) {
                            $this->baseAwardsQuery($user)($q)
                                ->where('SiteAwards.AwardType', AwardType::Mastery);
                        });
                    });
                    break;

                /*
                 * games where the player has a hardcore beaten award
                 */
                case GameListProgressFilterValue::EqBeatenHardcore->value:
                    $query->orWhere(function ($subQuery) use ($user) {
                        $subQuery->whereExists(function ($q) use ($user) {
                            $this->baseAwardsQuery($user)($q)
                                ->where('SiteAwards.AwardType', AwardType::GameBeaten)
                                ->where('SiteAwards.AwardDataExtra', UnlockMode::Hardcore);
                        })
                        ->whereNotExists(function ($q) use ($user) {
                            $this->baseAwardsQuery($user)($q)
                                ->where('SiteAwards.AwardType', AwardType::Mastery);
                        });
                    });
                    break;

                /*
                 * games where the player has any mastery award
                 * (includes mastered softcore, mastered hardcore)
                 */
                case GameListProgressFilterValue::GteCompleted->value:
                    $query->orWhereExists(function ($subQuery) use ($user) {
                        $this->baseAwardsQuery($user)($subQuery)
                            ->where('SiteAwards.AwardType', AwardType::Mastery);
                    });
                    break;

                /*
                 * games where the player has a softcore mastery (completion) award
                 */
                case GameListProgressFilterValue::EqCompleted->value:
                    $query->orWhere(function ($subQuery) use ($user) {
                        $subQuery->whereExists(function ($q) use ($user) {
                            $this->baseAwardsQuery($user)($q)
                                ->where('SiteAwards.AwardType', AwardType::Mastery)
                                ->where('SiteAwards.AwardDataExtra', UnlockMode::Softcore);
                        });
                    });
                    break;

                /*
                 * games where the player has a hardcore mastery award
                 */
                case GameListProgressFilterValue::EqMastered->value:
                    $query->orWhereExists(function ($subQuery) use ($user) {
                        $this->baseAwardsQuery($user)($subQuery)
                            ->where('SiteAwards.AwardType', AwardType::Mastery)
                            ->where('SiteAwards.AwardDataExtra', UnlockMode::Hardcore);
                    });
                    break;

                /*
                 * games where the player had mastered the game (in either mode)
                 * but due to set revisions no longer has unlocked all the achievements
                 */
                case GameListProgressFilterValue::Revised->value:
                    $query->orWhere(function ($query) use ($user) {
                        $query->whereExists(function ($subQuery) use ($user) {
                            // First, find games where they have the mastery award.
                            $this->baseAwardsQuery($user)($subQuery)
                                ->where(function ($q) use ($user) {
                                    $q->where(function ($q2) use ($user) {
                                        // Find games where the user has softcore mastery (completion),
                                        // but softcore completion is <100%.
                                        $q2->where('SiteAwards.AwardType', AwardType::Mastery)
                                            ->where('SiteAwards.AwardDataExtra', UnlockMode::Softcore)
                                            ->whereExists(function ($progress) use ($user) {
                                                $this->baseProgressQuery($user)($progress)
                                                    ->where('player_games.completion_percentage', '<', 1);
                                            });
                                    })
                                    ->orWhere(function ($q2) use ($user) {
                                        // Find games where the user has mastery, but completion is <100%.
                                        $q2->where('SiteAwards.AwardType', AwardType::Mastery)
                                            ->where('SiteAwards.AwardDataExtra', UnlockMode::Hardcore)
                                            ->whereExists(function ($progress) use ($user) {
                                                $this->baseProgressQuery($user)($progress)
                                                    ->where('player_games.completion_percentage_hardcore', '<', 1);
                                            });
                                    });
                                });
                        });
                    });
                    break;

                /*
                 * exclude games where the user has a mastery award
                 */
                case GameListProgressFilterValue::NeqMastered->value:
                    $query->orWhereNotExists(function ($subQuery) use ($user) {
                        $this->baseAwardsQuery($user)($subQuery)
                            ->where('SiteAwards.AwardType', AwardType::Mastery);
                    });
                    break;

                default:
                    break;
            }
        });
    }

    /**
     * Create a base subquery for checking a user's SiteAwards data.
     */
    private function baseAwardsQuery(User $user): Closure
    {
        return function ($query) use ($user) {
            return $query->select(DB::raw(1))
                ->from('SiteAwards')
                ->whereColumn('SiteAwards.AwardData', 'GameData.ID')
                ->where('SiteAwards.user_id', $user->id);
        };
    }

    /**
     * Create a base subquery for checking a user's player_games data.
     */
    private function baseProgressQuery(User $user): Closure
    {
        return function ($query) use ($user) {
            return $query->select(DB::raw(1))
                ->from('player_games')
                ->whereColumn('player_games.game_id', 'GameData.ID')
                ->where('player_games.user_id', $user->id);
        };
    }
}
