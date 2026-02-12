<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Actions\GetUserDeviceKindAction;
use App\Community\Data\CommentData;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Enums\UserGameListType;
use App\Community\Services\SubscriptionService;
use App\Data\UserPermissionsData;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\GameSet;
use App\Models\PlayerAchievementSet;
use App\Models\PlayerGame;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserGameAchievementSetPreference;
use App\Models\UserGameListEntry;
use App\Platform\Data\AchievementSetClaimData;
use App\Platform\Data\GameAchievementSetData;
use App\Platform\Data\GameData;
use App\Platform\Data\GameSetData;
use App\Platform\Data\GameSetRequestData;
use App\Platform\Data\GameShowPagePropsData;
use App\Platform\Data\PlayerAchievementSetData;
use App\Platform\Data\PlayerGameData;
use App\Platform\Data\PlayerGameProgressionAwardsData;
use App\Platform\Data\UserGameAchievementSetPreferenceData;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Enums\GameBannerPreference;
use App\Platform\Enums\GamePageListSort;
use App\Platform\Enums\GamePageListView;
use App\Platform\Services\GameLeaderboardService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Spatie\LaravelData\Lazy;

class BuildGameShowPagePropsAction
{
    public function __construct(
        protected BuildFollowedPlayerCompletionAction $buildFollowedPlayerCompletionAction,
        protected BuildGameAchievementDistributionAction $buildGameAchievementDistributionAction,
        protected BuildGameAggregateCreditsAction $buildGameAggregateCreditsAction,
        protected BuildGamePageClaimDataAction $buildGamePageClaimDataAction,
        protected BuildHubBreadcrumbsAction $buildHubBreadcrumbsAction,
        protected BuildSeriesHubDataAction $buildSeriesHubDataAction,
        protected GameLeaderboardService $gameLeaderboardService,
        protected LoadGameRecentPlayersAction $loadGameRecentPlayersAction,
        protected LoadGameTopAchieversAction $loadGameTopAchieversAction,
        protected ProcessGameReleasesForViewAction $processGameReleasesForViewAction,
        protected ResolveBackingGameForAchievementSetAction $resolveBackingGameForAchievementSetAction,
        protected ResolveHashesForAchievementSetAction $resolveHashesForAchievementSetAction,
    ) {
    }

    public function execute(
        Game $game,
        ?User $user,
        bool $isPromoted = true,
        ?GameAchievementSet $targetAchievementSet = null,
        GamePageListView $initialView = GamePageListView::Achievements,
        ?GamePageListSort $initialSort = null,
    ): GameShowPagePropsData {
        // The backing game is the legacy game that backs the target achievement set.
        // For core sets, this will be $game->id. For subsets, it'll be a different ID.
        $backingGameId = $targetAchievementSet !== null
            ? $this->resolveBackingGameForAchievementSetAction->execute($targetAchievementSet->achievement_set_id)
            : null;

        if ($backingGameId && $backingGameId !== $game->id) {
            $backingGame = Game::find($backingGameId);

            // These relationships are already eager-loaded for $game but not for a different backing game.
            $backingGame->load([
                'achievementSetClaims' => function ($query) {
                    $query->whereIn('status', [ClaimStatus::Active, ClaimStatus::InReview])
                        ->with('user');
                },
                'leaderboards' => function ($query) {
                    $query->where('order_column', '>=', 0)
                        ->orderBy('order_column')
                        ->with(['topEntry.user']);
                },
                'visibleComments' => function ($query) {
                    $query->latest('created_at')
                        ->limit(20)
                        ->with(['user' => function ($userQuery) {
                            $userQuery->withTrashed();
                        }]);
                },
            ]);
        } else {
            $backingGame = $game;
        }

        [$numMasters, $topAchievers, $numCompletions, $numBeaten, $numBeatenSoftcore] =
            $this->loadGameTopAchieversAction->execute($backingGame);

        $playerGame = $user
            ? $user->playerGames()->whereGameId($backingGame->id)->first()
            : null;

        // Attach unlock times directly to achievements rather than stitching
        // through eager loading, which is complex for this polymorphic shape.
        if ($user) {
            $achievementIds = $game->gameAchievementSets
                ->flatMap(fn ($gas) => $gas->achievementSet->achievements->pluck('id'))
                ->unique();

            $playerAchievements = $user->playerAchievements()
                ->whereIn('achievement_id', $achievementIds)
                ->get()
                ->keyBy('achievement_id');

            foreach ($game->gameAchievementSets as $gas) {
                foreach ($gas->achievementSet->achievements as $achievement) {
                    $playerAchievement = $playerAchievements->get($achievement->id);

                    if ($playerAchievement) {
                        $achievement->player_unlocked_at = $playerAchievement->unlocked_at;
                        $achievement->player_unlocked_hardcore_at = $playerAchievement->unlocked_hardcore_at;
                    }
                }
            }
        }

        $playerAchievementSets = collect();
        if ($user) {
            $achievementSetIds = $game->gameAchievementSets->pluck('achievement_set_id')->unique();

            $playerAchievementSets = $user->playerAchievementSets()
                ->whereIn('achievement_set_id', $achievementSetIds)
                ->get()
                ->mapWithKeys(fn ($pas) => [
                    $pas->achievement_set_id => PlayerAchievementSetData::fromPlayerAchievementSet($pas)
                        ->include('timeTaken', 'timeTakenHardcore'),
                ]);
        }

        $similarGames = $game
            ->similarGamesList
            ->filter(
                fn ($game) => !str_contains($game->title, '[Subset')
            )
            ->sortBy('sort_title')
            ->sortByDesc(fn ($game) => $game->achievements_published > 0);

        // Filter hubs the user doesn't have permission to view.
        /** @see GameSetPolicy.php */
        $relatedHubs = $game->hubs
            ->filter(function ($hub) use ($user) {
                // If the user is a guest, only show hubs without view restrictions.
                if (!$user) {
                    return !$hub->has_view_role_requirement;
                }

                return $user->can('view', $hub);
            })
            ->map(function ($hub) {
                // Check if the hub is an event hub or has an event hub ancestor.
                $isEventHub = $hub->is_event_hub;

                if (!$isEventHub) {
                    // Use breadcrumbs to check if any ancestor is an event hub.
                    $breadcrumbs = $this->buildHubBreadcrumbsAction->execute($hub);
                    foreach ($breadcrumbs as $ancestor) {
                        if ($ancestor->id !== GameSet::CentralHubId && ($ancestor->isEventHub ?? false)) {
                            $isEventHub = true;
                            break;
                        }
                    }
                }

                $data = GameSetData::from($hub)->include('isEventHub');

                // Always remove updatedAt.
                $data = $data->except('updatedAt');

                // Override isEventHub if needed.
                if ($isEventHub && !$hub->is_event_hub) {
                    $data->isEventHub = true;
                } elseif (!$isEventHub) {
                    $data = $data->except('isEventHub');
                }

                return $data;
            })
            ->values()
            ->all();

        $initialUserGameListState = $this->getInitialUserGameListState($backingGame, $user);
        $achievementSetClaims = $this->buildAchievementSetClaims($backingGame, $user);
        $claimData = $this->buildGamePageClaimDataAction->execute($backingGame, $user, $backingGame->achievementSetClaims);

        // Deduplicate releases by region for proper display.
        $processedReleases = $this->processGameReleasesForViewAction->execute($game);
        $game->setRelation('releases', collect($processedReleases));

        $isLockedOnlyFilterEnabled = $this->getIsGameIdInCookie('hide_unlocked_achievements_games', $game->id);
        $isMissableOnlyFilterEnabled = $this->getIsGameIdInCookie('hide_nonmissable_achievements_games', $game->id);

        // The primary claim is needed for permission checking (eg: can user update achievements).
        $primaryClaim = $backingGame->achievementSetClaims
            ->where('claim_type', ClaimType::Primary)
            ->whereIn('status', [ClaimStatus::Active, ClaimStatus::InReview])
            ->first();

        $isMobile = (new GetUserDeviceKindAction())->execute() === 'mobile';
        $defaultSort = $this->getDefaultSort($backingGame, $playerGame);

        // Non-core sets need real player counts from player_achievement_sets,
        // otherwise the UI falls back to the base game player count.
        $targetAchievementSetPlayersTotal = null;
        $targetAchievementSetPlayersHardcore = null;
        if ($targetAchievementSet !== null && $targetAchievementSet->type !== AchievementSetType::Core) {
            [$targetAchievementSetPlayersTotal, $targetAchievementSetPlayersHardcore] =
                $this->getAchievementSetPlayerCounts($targetAchievementSet->achievement_set_id);
        }

        $subscriptionService = new SubscriptionService();

        // Pre-calculate once so both allLeaderboards and featuredLeaderboards share the same data.
        [$userLeaderboardEntries, $userLeaderboardRanks] = $this->gameLeaderboardService->getUserLeaderboardData($backingGame, $user);

        $propsData = new GameShowPagePropsData(
            achievementSetClaims: $achievementSetClaims,

            allLeaderboards: request()->inertia() || $initialView === GamePageListView::Leaderboards
                ? $this->gameLeaderboardService->buildLeaderboards($backingGame, $user, $userLeaderboardEntries, $userLeaderboardRanks, showUnpublished: request()->boolean('unpublished'))
                : Lazy::inertiaDeferred(fn () => $this->gameLeaderboardService->buildLeaderboards($backingGame, $user, $userLeaderboardEntries, $userLeaderboardRanks, showUnpublished: request()->boolean('unpublished'))),

            can: UserPermissionsData::fromUser($user, game: $backingGame, claim: $primaryClaim)->include(
                'createAchievementSetClaims',
                'createGameComments',
                'createGameForumTopic',
                'manageAchievementSetClaims',
                'manageGameHashes',
                'manageGames',
                'reviewAchievementSetClaims',
                'updateAnyAchievementSetClaim',
                'updateGame',
                'viewDeveloperInterest',
            ),

            defaultSort: $defaultSort,
            initialSort: $initialSort ?? $defaultSort,
            initialView: $initialView,

            game: GameData::fromGame($game)->include(
                'achievementsPublished',
                'badgeUrl',
                'developer',
                'forumTopicId',
                'gameAchievementSets.achievementSet.achievementGroups',
                'gameAchievementSets.achievementSet.achievements.createdAt',
                'gameAchievementSets.achievementSet.achievements.description',
                'gameAchievementSets.achievementSet.achievements.developer',
                'gameAchievementSets.achievementSet.achievements.groupId',
                'gameAchievementSets.achievementSet.achievements.orderColumn',
                'gameAchievementSets.achievementSet.achievements.points',
                'gameAchievementSets.achievementSet.achievements.pointsWeighted',
                'gameAchievementSets.achievementSet.achievements.type',
                'gameAchievementSets.achievementSet.achievements.unlockedAt',
                'gameAchievementSets.achievementSet.achievements.unlockedHardcoreAt',
                'gameAchievementSets.achievementSet.achievements.unlockPercentage',
                'gameAchievementSets.achievementSet.achievements.unlocksHardcore',
                'gameAchievementSets.achievementSet.achievements.unlocksTotal',
                'gameAchievementSets.achievementSet.medianTimeToComplete',
                'gameAchievementSets.achievementSet.medianTimeToCompleteHardcore',
                'gameAchievementSets.achievementSet.timesCompleted',
                'gameAchievementSets.achievementSet.timesCompletedHardcore',
                'genre',
                'imageBoxArtUrl',
                'imageIngameUrl',
                'imageTitleUrl',
                'medianTimeToBeat',
                'medianTimeToBeatHardcore',
                'playersHardcore',
                'playersTotal',
                'pointsTotal',
                'publisher',
                'releases',
                'system.active',
                'system.iconUrl',
                'system.nameShort',
                'system',
                'timesBeaten',
                'timesBeatenHardcore',
            ),

            similarGames: $similarGames->map(fn ($game) => GameData::fromGame($game)->include(
                'achievementsPublished',
                'badgeUrl',
                'system.iconUrl',
                'system.nameShort',
                'pointsTotal',
                'pointsWeighted',
            ))->values()->all(),

            aggregateCredits: $this->buildGameAggregateCreditsAction->execute($game),

            backingGame: GameData::fromGame($backingGame)->include(
                'achievementsPublished',
                'achievementsUnpublished',
                'badgeUrl',
                'forumTopicId',
                'guideUrl',
                'pointsTotal',
            ),

            claimData: $claimData,
            featuredLeaderboards: Lazy::create(fn () => $this->gameLeaderboardService->buildLeaderboards($backingGame, $user, $userLeaderboardEntries, $userLeaderboardRanks, 5, true, false)),
            hasMatureContent: $backingGame->hasMatureContent,
            hubs: $relatedHubs,
            isOnWantToDevList: $initialUserGameListState['isOnWantToDevList'],
            isOnWantToPlayList: $initialUserGameListState['isOnWantToPlayList'],
            isSubscribedToAchievementComments: $user ? $subscriptionService->isSubscribed($user, SubscriptionSubjectType::GameAchievements, $backingGame->id) : false,
            isSubscribedToComments: $user ? $subscriptionService->isSubscribed($user, SubscriptionSubjectType::GameWall, $backingGame->id) : false,
            isSubscribedToTickets: $user ? $subscriptionService->isSubscribed($user, SubscriptionSubjectType::GameTickets, $backingGame->id) : false,
            isLockedOnlyFilterEnabled: $isLockedOnlyFilterEnabled,
            isMissableOnlyFilterEnabled: $isMissableOnlyFilterEnabled,
            isViewingPublishedAchievements: $isPromoted,
            followedPlayerCompletions: $this->buildFollowedPlayerCompletionAction->execute($user, $backingGame),

            playerAchievementChartBuckets: $isPromoted
                ? $this->buildGameAchievementDistributionAction->execute($backingGame, $user)
                : collect(),

            numComments: $backingGame->visibleComments($user)->count(),
            numCompatibleHashes: $this->getCompatibleHashesCount($game, $targetAchievementSet),
            numCompletions: $numCompletions,
            numBeaten: $numBeaten,
            numBeatenSoftcore: $numBeatenSoftcore,
            numInterestedDevelopers: $this->getInterestedDevelopersCount($backingGame, $user),
            numLeaderboards: $this->gameLeaderboardService->getCount($backingGame, $isPromoted),
            numMasters: $numMasters,

            numOpenTickets: $isPromoted
                ? Ticket::forGame($backingGame)->unresolved()->officialCore()->count()
                : Ticket::forGame($backingGame)->unresolved()->unofficial()->count(),

            recentPlayers: $this->loadGameRecentPlayersAction->execute($backingGame),
            recentVisibleComments: Collection::make(array_reverse(CommentData::fromCollection($backingGame->visibleComments))),
            topAchievers: $topAchievers,
            playerGame: $playerGame
                ? PlayerGameData::fromPlayerGame($playerGame)->include('lastPlayedAt', 'playtimeTotal', 'timeToBeat', 'timeToBeatHardcore')
                : null,
            playerGameProgressionAwards: $user
                ? PlayerGameProgressionAwardsData::fromArray(getUserGameProgressionAwards($backingGame->id, $user))
                : null,
            playerAchievementSets: $playerAchievementSets,
            bannerPreference: GameBannerPreference::tryFrom(Cookie::get('banner_state') ?? '') ?? GameBannerPreference::Normal,
            seriesHub: $this->buildSeriesHubDataAction->execute($game),
            setRequestData: $this->buildSetRequestData($backingGame, $user),
            banner: $game->banner,
            targetAchievementSetId: $targetAchievementSet?->achievement_set_id,
            targetAchievementSetPlayersTotal: $targetAchievementSetPlayersTotal,
            targetAchievementSetPlayersHardcore: $targetAchievementSetPlayersHardcore,

            selectableGameAchievementSets: $game->getAttribute('selectableGameAchievementSets')
                ->map(function ($gas) {
                    $gas->achievementSet->setRelation('achievements', collect());

                    $gas->achievementSet->median_time_to_complete ??= 0;
                    $gas->achievementSet->median_time_to_complete_hardcore ??= 0;
                    $gas->achievementSet->players_hardcore ??= 0;
                    $gas->achievementSet->players_total ??= 0;

                    return GameAchievementSetData::from($gas)->include(
                        'type',
                        'title',
                        'achievementSet.id',
                        'achievementSet.imageAssetPathUrl',
                        'achievementSet.achievementsFirstPublishedAt',
                        'achievementSet.achievementsPublished',
                        'achievementSet.pointsTotal',
                        'achievementSet.pointsWeighted',
                        'achievementSet.achievements', // this will always be empty
                    );
                })
                ->values()
                ->all(),

            userGameAchievementSetPreferences: $this->buildUserAchievementSetPreferences($game, $user),
        );

        // Only include featured leaderboards for non-mobile devices.
        if (!$isMobile) {
            $propsData = $propsData->include('featuredLeaderboards');
        }

        return $propsData;
    }

    /**
     * @return Collection<int, AchievementSetClaimData>
     */
    private function buildAchievementSetClaims(Game $game, ?User $user): Collection
    {
        // Privileged roles can see when a claimant last played the game.
        $claimIncludes = ['user', 'claimType', 'finishedAt', 'status'];
        if ($user && $user->hasAnyRole([Role::DEV_COMPLIANCE, Role::MODERATOR, Role::ADMINISTRATOR])) {
            $claimIncludes[] = 'userLastPlayedAt';
        }

        return $game->achievementSetClaims
            ->map(fn ($claim) => AchievementSetClaimData::fromAchievementSetClaim($claim)->include(...$claimIncludes))
            ->values();
    }

    /**
     * @return Collection<int, UserGameAchievementSetPreferenceData>
     */
    private function buildUserAchievementSetPreferences(Game $game, ?User $user): Collection
    {
        if (!$user) {
            return collect();
        }

        $gameAchievementSetIds = $game->selectableGameAchievementSets()->pluck('id');

        return UserGameAchievementSetPreference::where('user_id', $user->id)
            ->whereIn('game_achievement_set_id', $gameAchievementSetIds)
            ->get()
            ->mapWithKeys(fn ($preference) => [
                $preference->game_achievement_set_id => UserGameAchievementSetPreferenceData::fromUserGameAchievementSetPreference($preference),
            ]);
    }

    /**
     * TODO also support set requests
     */
    private function getInitialUserGameListState(Game $game, ?User $user): array
    {
        if (!$user) {
            return [
                'isOnWantToDevList' => false,
                'isOnWantToPlayList' => false,
            ];
        }

        $results = UserGameListEntry::where('user_id', $user->id)
            ->where('game_id', $game->id)
            ->pluck('type')
            ->toArray();

        return [
            'isOnWantToDevList' => in_array(UserGameListType::Develop, $results),
            'isOnWantToPlayList' => in_array(UserGameListType::Play, $results),
        ];
    }

    private function getIsGameIdInCookie(string $cookieName, int $gameId): bool
    {
        $cookieValue = Cookie::get($cookieName);
        if (!$cookieValue) {
            return false;
        }

        $gameIds = array_filter(array_map('intval', explode(',', $cookieValue)));

        return in_array($gameId, $gameIds);
    }

    private function getCompatibleHashesCount(Game $game, ?GameAchievementSet $targetAchievementSet): int
    {
        // Reuse the Supported Game Files page logic to ensure consistent counts.
        return $this->resolveHashesForAchievementSetAction
            ->execute($game, $targetAchievementSet)
            ->count();
    }

    private function buildSetRequestData(Game $backingGame, ?User $user): ?GameSetRequestData
    {
        // Set requests are only relevant for games/sets that don't have achievements yet.
        if ($backingGame->achievements_published > 0) {
            return null;
        }

        $totalRequests = getSetRequestCount($backingGame->id);

        if (!$user) {
            return new GameSetRequestData(
                hasUserRequestedSet: false,
                totalRequests: $totalRequests,
                userRequestsRemaining: 0,
            );
        }

        $userRequestInfo = getUserRequestsInformation($user, $backingGame->id);

        return new GameSetRequestData(
            hasUserRequestedSet: (bool) $userRequestInfo['requestedThisGame'],
            totalRequests: $totalRequests,
            userRequestsRemaining: $userRequestInfo['remaining'],
        );
    }

    private function getInterestedDevelopersCount(Game $game, ?User $user): ?int
    {
        if (!$user || !$user->can('viewDeveloperInterest', $game)) {
            return null;
        }

        return User::query()
            ->whereIn('id', function ($query) use ($game) {
                $query->select('user_id')
                    ->from('user_game_list_entries')
                    ->where('game_id', $game->id)
                    ->where('type', UserGameListType::Develop);
            })
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', [Role::DEVELOPER, Role::DEVELOPER_JUNIOR]);
            })
            ->count();
    }

    private function getDefaultSort(Game $backingGame, ?PlayerGame $playerGame): GamePageListSort
    {
        // 'Normal' sort groups locked/unlocked achievements, which only makes
        // sense when the user has partial progress. Otherwise, use display order.
        if ($playerGame) {
            $unlockedCount = $playerGame->achievements_unlocked ?? 0;
            $totalCount = $backingGame->achievements_published ?? 0;

            if ($unlockedCount > 0 && $unlockedCount < $totalCount) {
                return GamePageListSort::Normal;
            }
        }

        return GamePageListSort::DisplayOrder;
    }

    /**
     * Get actual player counts for a specific achievement set from player_achievement_sets.
     *
     * @return array{int, int} [$playersTotal, $playersHardcore]
     */
    private function getAchievementSetPlayerCounts(int $achievementSetId): array
    {
        $playersTotal = PlayerAchievementSet::query()
            ->where('achievement_set_id', $achievementSetId)
            ->where('achievements_unlocked', '>', 0)
            ->leftJoin('unranked_users', 'player_achievement_sets.user_id', '=', 'unranked_users.user_id')
            ->whereNull('unranked_users.id')
            ->count();

        $playersHardcore = PlayerAchievementSet::query()
            ->where('achievement_set_id', $achievementSetId)
            ->where('achievements_unlocked_hardcore', '>', 0)
            ->leftJoin('unranked_users', 'player_achievement_sets.user_id', '=', 'unranked_users.user_id')
            ->whereNull('unranked_users.id')
            ->count();

        return [$playersTotal, $playersHardcore];
    }
}
