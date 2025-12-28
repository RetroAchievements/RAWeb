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
use App\Enums\GameHashCompatibility;
use App\Models\AchievementAuthor;
use App\Models\AchievementMaintainer;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\GameSet;
use App\Models\LeaderboardEntry;
use App\Models\PlayerAchievementSet;
use App\Models\PlayerGame;
use App\Models\Role;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserGameAchievementSetPreference;
use App\Models\UserGameListEntry;
use App\Platform\Data\AchievementSetClaimData;
use App\Platform\Data\AggregateAchievementSetCreditsData;
use App\Platform\Data\GameAchievementSetData;
use App\Platform\Data\GameData;
use App\Platform\Data\GameSetData;
use App\Platform\Data\GameSetRequestData;
use App\Platform\Data\GameShowPagePropsData;
use App\Platform\Data\LeaderboardData;
use App\Platform\Data\LeaderboardEntryData;
use App\Platform\Data\PlayerAchievementSetData;
use App\Platform\Data\PlayerGameData;
use App\Platform\Data\PlayerGameProgressionAwardsData;
use App\Platform\Data\UserCreditsData;
use App\Platform\Data\UserGameAchievementSetPreferenceData;
use App\Platform\Enums\AchievementAuthorTask;
use App\Platform\Enums\AchievementSetAuthorTask;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Enums\GamePageListSort;
use App\Platform\Enums\GamePageListView;
use App\Platform\Enums\LeaderboardState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Lazy;

class BuildGameShowPagePropsAction
{
    public function __construct(
        protected BuildFollowedPlayerCompletionAction $buildFollowedPlayerCompletionAction,
        protected BuildGameAchievementDistributionAction $buildGameAchievementDistributionAction,
        protected LoadGameTopAchieversAction $loadGameTopAchieversAction,
        protected BuildSeriesHubDataAction $buildSeriesHubDataAction,
        protected ResolveBackingGameForAchievementSetAction $resolveBackingGameForAchievementSetAction,
        protected LoadGameRecentPlayersAction $loadGameRecentPlayersAction,
        protected ProcessGameReleasesForViewAction $processGameReleasesForViewAction,
        protected BuildGamePageClaimDataAction $buildGamePageClaimDataAction,
        protected BuildHubBreadcrumbsAction $buildHubBreadcrumbsAction,
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
        $backingGameId = null;

        if ($targetAchievementSet !== null) {
            $backingGameId = $this->resolveBackingGameForAchievementSetAction->execute(
                $targetAchievementSet->achievement_set_id
            );
        }

        // If we have a backing game ID different from the current game, load it.
        // Otherwise, use the current game as the backing game.
        $backingGame = null;
        if ($backingGameId && $backingGameId !== $game->id) {
            $backingGame = Game::find($backingGameId);

            // Load the achievement set claim and visible comment relationships for the backing game.
            $backingGame->load([
                'achievementSetClaims' => function ($query) {
                    $query->whereIn('Status', [ClaimStatus::Active, ClaimStatus::InReview])
                        ->with('user');
                },
                'leaderboards' => function ($query) {
                    $query->where('DisplayOrder', '>=', 0) // only show visible leaderboards on the page
                        ->orderBy('DisplayOrder')
                        ->with(['topEntry.user']);
                },
                'visibleComments' => function ($query) {
                    $query->latest('Submitted')
                        ->limit(20)
                        ->with(['user' => function ($userQuery) {
                            $userQuery->withTrashed();
                        }]);
                },
            ]);
        } else {
            // Use the current game as the backing game.
            $backingGame = $game;
        }

        [$numMasters, $topAchievers, $numCompletions, $numBeaten, $numBeatenSoftcore] =
            $this->loadGameTopAchieversAction->execute($backingGame);

        $playerGame = $user
            ? $user->playerGames()->whereGameId($backingGame->id)->first()
            : null;

        // Attach PlayerAchievement records directly to the achievements collection.
        // This is much easier than trying to stitch them together through eager loading.
        if ($user) {
            // First, collect all achievement IDs.
            $achievementIds = $game->gameAchievementSets
                ->flatMap(fn ($gas) => $gas->achievementSet->achievements->pluck('id'))
                ->unique();

            // Then, fetch PlayerAchievement records for these IDs.
            $playerAchievements = $user->playerAchievements()
                ->whereIn('achievement_id', $achievementIds)
                ->get()
                ->keyBy('achievement_id');

            // Finally, directly modify the actual models in the game object.
            foreach ($game->gameAchievementSets as $gasIndex => $gas) {
                foreach ($gas->achievementSet->achievements as $achIndex => $achievement) {
                    // For each achievement, find if we have a player achievement.
                    $playerAchievement = $playerAchievements->get($achievement->id);

                    if ($playerAchievement) {
                        // Create custom properties on the model to store the unlock times.
                        $achievement->player_unlocked_at = $playerAchievement->unlocked_at;
                        $achievement->player_unlocked_hardcore_at = $playerAchievement->unlocked_hardcore_at;
                    }
                }
            }
        }

        // Load the user's player_achievement_sets entities.
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

        /**
         * If the user doesn't have permission to view a related hub,
         * we should filter it out of the list.
         * @see GameSetPolicy.php
         */
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

        // Deduplicate releases by region and sort them by date.
        // Then, override the releases in the game object for proper display.
        $processedReleases = $this->processGameReleasesForViewAction->execute($game);
        $game->setRelation('releases', collect($processedReleases));

        // Check cookies for filter states.
        $isLockedOnlyFilterEnabled = $this->getIsGameIdInCookie('hide_unlocked_achievements_games', $game->id);
        $isMissableOnlyFilterEnabled = $this->getIsGameIdInCookie('hide_nonmissable_achievements_games', $game->id);

        // Get the primary claim from the already-loaded claims for permission checking.
        $primaryClaim = $backingGame->achievementSetClaims
            ->where('ClaimType', ClaimType::Primary)
            ->whereIn('Status', [ClaimStatus::Active, ClaimStatus::InReview])
            ->first();

        // Detect if the user is on mobile to conditionally include some props.
        $isMobile = (new GetUserDeviceKindAction())->execute() === 'mobile';

        // Derive the default sort order based on the user's unlock progress.
        $defaultSort = $this->getDefaultSort($backingGame, $playerGame);

        // Calculate target set player counts for non-core sets.
        // This is used for the 'Playtime Stats' component. We need to get the real
        // subset player count from player_achievement_sets, otherwise the UI will
        // fall back to the base game player count.
        $targetAchievementSetPlayersTotal = null;
        $targetAchievementSetPlayersHardcore = null;
        if ($targetAchievementSet !== null && $targetAchievementSet->type !== AchievementSetType::Core) {
            [$targetAchievementSetPlayersTotal, $targetAchievementSetPlayersHardcore] =
                $this->getAchievementSetPlayerCounts($targetAchievementSet->achievement_set_id);
        }

        $subscriptionService = new SubscriptionService();

        $propsData = new GameShowPagePropsData(
            achievementSetClaims: $achievementSetClaims,

            allLeaderboards: request()->inertia() || $initialView === GamePageListView::Leaderboards
                ? $this->buildAllLeaderboards($backingGame, $user)
                : Lazy::inertiaDeferred(fn () => $this->buildAllLeaderboards($backingGame, $user)),

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

            aggregateCredits: $this->buildAggregateCredits($game),

            backingGame: GameData::fromGame($backingGame)->include(
                'achievementsPublished',
                'achievementsUnpublished',
                'badgeUrl',
                'forumTopicId',
                'guideUrl',
                'pointsTotal',
            ),

            claimData: $claimData,
            featuredLeaderboards: Lazy::create(fn () => $this->buildLeaderboards($backingGame, $user, 5, true, false)), // Only show active leaderboards in the featured list
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
            numCompatibleHashes: $this->getCompatibleHashesCount($game, $backingGame, $targetAchievementSet),
            numCompletions: $numCompletions,
            numBeaten: $numBeaten,
            numBeatenSoftcore: $numBeatenSoftcore,
            numInterestedDevelopers: $this->getInterestedDevelopersCount($backingGame, $user),
            numLeaderboards: $this->getLeaderboardsCount($backingGame, $isPromoted),
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
            seriesHub: $this->buildSeriesHubDataAction->execute($game),
            setRequestData: $this->buildSetRequestData($backingGame, $user),
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
        // Build the include array based on current user permissions.
        $claimIncludes = ['user', 'claimType', 'finishedAt', 'status'];
        if ($user && $user->hasAnyRole([Role::DEV_COMPLIANCE, Role::MODERATOR, Role::ADMINISTRATOR])) {
            $claimIncludes[] = 'userLastPlayedAt';
        }

        return $game->achievementSetClaims
            ->map(fn ($claim) => AchievementSetClaimData::fromAchievementSetClaim($claim)->include(...$claimIncludes))
            ->values();
    }

    private function buildAggregateCredits(Game $game): AggregateAchievementSetCreditsData
    {
        // Initialize credit counts by task and user.
        $achievementsAuthors = collect();
        $achievementsMaintainers = collect();
        $achievementSetArtworkCredits = collect();
        $achievementsArtworkCredits = collect();
        $achievementsDesignCredits = collect();
        $achievementsLogicCredits = collect();
        $achievementsTestingCredits = collect();
        $achievementsWritingCredits = collect();
        $hashCompatibilityTestingCredits = collect();

        // Collect all achievement IDs for subsequent aggregation queries.
        $achievementIds = $game->gameAchievementSets
            ->pluck('achievementSet.achievements.*.id')
            ->flatten()
            ->unique()
            ->filter()
            ->values();

        // Process achievement set authors. Right now, we only support badge artwork as a task.
        foreach ($game->gameAchievementSets as $gameAchievementSet) {
            $achievementSet = $gameAchievementSet->achievementSet;

            // Get only the most recent artwork author for this achievement set.
            $mostRecentArtworkAuthor = $achievementSet->achievementSetAuthors
                ->filter(fn ($author) => $author->task === AchievementSetAuthorTask::Artwork)
                ->sortByDesc('created_at')
                ->first();

            if ($mostRecentArtworkAuthor) {
                $userId = $mostRecentArtworkAuthor->user_id;
                $existing = $achievementSetArtworkCredits->get($userId);

                $achievementSetArtworkCredits->put($userId, [
                    'user' => $mostRecentArtworkAuthor->user,
                    'count' => ($existing['count'] ?? 0) + 1,
                    'created_at' => $mostRecentArtworkAuthor->created_at,
                ]);
            }
        }

        // Process hash compatibility testing credits.
        // Credit is given to users who successfully tested hash compatibility.
        $compatibleHashes = $game->hashes()
            ->where('compatibility', GameHashCompatibility::Compatible)
            ->whereNotNull('compatibility_tester_id')
            ->whereColumn('compatibility_tester_id', '!=', 'user_id')
            ->with('compatibilityTester')
            ->get();
        foreach ($compatibleHashes as $hash) {
            if ($hash->compatibilityTester) {
                $hashCompatibilityTestingCredits->put($hash->compatibilityTester->id, [
                    'user' => $hash->compatibilityTester,
                    'count' => 0,
                    'created_at' => $hash->updated_at,
                ]);
            }
        }

        // Process achievement authors (developers) - these are already loaded.
        foreach ($game->gameAchievementSets as $gameAchievementSet) {
            $achievementSet = $gameAchievementSet->achievementSet;

            foreach ($achievementSet->achievements as $achievement) {
                // Count original achievement authors.
                if ($achievement->developer) {
                    $userId = $achievement->developer->id;
                    $achievementsAuthors->put($userId, [
                        'user' => $achievement->developer,
                        'count' => ($achievementsAuthors->get($userId)['count'] ?? 0) + 1,
                    ]);
                }
            }
        }

        // Use aggregation query for active maintainers.
        if ($achievementIds->isNotEmpty()) {
            $maintainerStats = AchievementMaintainer::query()
                ->join('achievements', 'achievement_maintainers.achievement_id', '=', 'achievements.id')
                ->whereIn('achievement_maintainers.achievement_id', $achievementIds)
                ->where('achievement_maintainers.is_active', true)
                ->whereColumn('achievement_maintainers.user_id', '!=', 'achievements.user_id')
                ->select('achievement_maintainers.user_id', DB::raw('COUNT(*) as count'), DB::raw('MAX(achievement_maintainers.effective_from) as latest_date'))
                ->with('user')
                ->groupBy('achievement_maintainers.user_id')
                ->get();

            foreach ($maintainerStats as $stat) {
                if ($stat->user && !$stat->user->trashed()) {
                    $achievementsMaintainers->put($stat->user_id, [
                        'user' => $stat->user,
                        'count' => $stat->count,
                        'created_at' => $stat->latest_date ? Carbon::parse($stat->latest_date) : null,
                    ]);
                }
            }
        }

        // Use an aggregation query for achievement authorship credits.
        if ($achievementIds->isNotEmpty()) {
            $authorshipStats = AchievementAuthor::query()
                ->whereIn('achievement_id', $achievementIds)
                ->select('user_id', 'task', DB::raw('COUNT(*) as count'))
                ->with('user')
                ->groupBy('user_id', 'task')
                ->get();

            foreach ($authorshipStats as $stat) {
                if (!$stat->user || $stat->user->trashed()) {
                    continue;
                }

                $userId = $stat->user_id;
                $user = $stat->user;
                $count = $stat->count;

                switch ($stat->task) {
                    case AchievementAuthorTask::Artwork->value:
                        $achievementsArtworkCredits->put($userId, [
                            'user' => $user,
                            'count' => $count,
                        ]);
                        break;

                    case AchievementAuthorTask::Design->value:
                        $achievementsDesignCredits->put($userId, [
                            'user' => $user,
                            'count' => $count,
                        ]);
                        break;

                    case AchievementAuthorTask::Logic->value:
                        $achievementsLogicCredits->put($userId, [
                            'user' => $user,
                            'count' => $count,
                        ]);
                        break;

                    case AchievementAuthorTask::Testing->value:
                        $achievementsTestingCredits->put($userId, [
                            'user' => $user,
                            'count' => $count,
                        ]);
                        break;

                    case AchievementAuthorTask::Writing->value:
                        $achievementsWritingCredits->put($userId, [
                            'user' => $user,
                            'count' => $count,
                        ]);
                        break;
                }
            }
        }

        // Convert to UserCreditsData arrays sorted by count descending.
        $sortByCountDesc = fn ($credits, $includeTrash = false) => $credits
            ->filter(fn ($item) => $includeTrash || !$item['user']->trashed())
            ->sortByDesc('count')
            ->map(fn ($item) => UserCreditsData::fromUserWithCount(
                $item['user'],
                $item['count'],
                isset($item['created_at']) ? $item['created_at'] : null
            )->include('isGone'))
            ->values()
            ->all();

        return new AggregateAchievementSetCreditsData(
            achievementsAuthors: $sortByCountDesc($achievementsAuthors, true), // Include trashed users for original authors.
            achievementsMaintainers: $sortByCountDesc($achievementsMaintainers),
            achievementsArtwork: $sortByCountDesc($achievementsArtworkCredits),
            achievementsDesign: $sortByCountDesc($achievementsDesignCredits),
            achievementSetArtwork: $sortByCountDesc($achievementSetArtworkCredits),
            achievementsLogic: $sortByCountDesc($achievementsLogicCredits),
            achievementsTesting: $sortByCountDesc($achievementsTestingCredits),
            achievementsWriting: $sortByCountDesc($achievementsWritingCredits),
            hashCompatibilityTesting: $sortByCountDesc($hashCompatibilityTestingCredits),
        );
    }

    /**
     * @return Collection<int, UserGameAchievementSetPreferenceData>
     */
    private function buildUserAchievementSetPreferences(Game $game, ?User $user): Collection
    {
        $userGameAchievementSetPreferences = collect();
        if ($user) {
            $gameAchievementSetIds = $game->selectableGameAchievementSets()->pluck('id');

            $userGameAchievementSetPreferences = UserGameAchievementSetPreference::where('user_id', $user->id)
                ->whereIn('game_achievement_set_id', $gameAchievementSetIds)
                ->get()
                ->mapWithKeys(fn ($preference) => [
                    $preference->game_achievement_set_id => UserGameAchievementSetPreferenceData::fromUserGameAchievementSetPreference($preference),
                ]);
        }

        return $userGameAchievementSetPreferences;
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
            ->where('GameID', $game->id)
            ->get(['type'])
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

    private function getCompatibleHashesCount(Game $game, Game $backingGame, ?GameAchievementSet $targetAchievementSet): int
    {
        // Use the backing game's hashes for Specialty and Exclusive set types.
        if ($targetAchievementSet !== null) {
            $setType = $targetAchievementSet->type;
            if (in_array($setType, [
                AchievementSetType::Specialty,
                AchievementSetType::WillBeSpecialty,
                AchievementSetType::Exclusive,
                AchievementSetType::WillBeExclusive,
            ])) {
                return $backingGame->hashes->where('compatibility', GameHashCompatibility::Compatible)->count();
            }
        }

        // Otherwise use the main game's hashes.
        return $game->hashes->where('compatibility', GameHashCompatibility::Compatible)->count();
    }

    private function buildSetRequestData(Game $backingGame, ?User $user): ?GameSetRequestData
    {
        // Only return set request data for games without achievements.
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

    /**
     * @return Collection<int, LeaderboardData>
     */
    private function buildAllLeaderboards(Game $game, ?User $user = null): Collection
    {
        $showUnpublished = request()->boolean('unpublished');

        return $this->buildLeaderboards($game, $user, null, activeOnly: false, showUnpublished: $showUnpublished);
    }

    /**
     * @return Collection<int, LeaderboardData>
     */
    private function buildLeaderboards(Game $game, ?User $user = null, ?int $limit = null, bool $activeOnly = false, bool $showUnpublished = false): Collection
    {
        // Only show leaderboards if the system is active and it's not an event game.
        if (!$game->system->active || $game->system->id === System::Events) {
            return collect();
        }

        $allowedLeaderboardStates = match (true) {
            $activeOnly => [LeaderboardState::Active],
            $showUnpublished => [LeaderboardState::Unpublished],
            default => [LeaderboardState::Active, LeaderboardState::Disabled],
        };

        $leaderboards = $game->leaderboards
            ->whereIn('state', $allowedLeaderboardStates)
            ->values();

        if (!$activeOnly) {
            // Sort: Active/Unpublished first, Disabled last, then by DisplayOrder.
            $leaderboards = $leaderboards->sortBy([
                fn ($leaderboard) => $leaderboard->state === LeaderboardState::Disabled ? 1 : 0,
                fn ($a, $b) => $a->DisplayOrder <=> $b->DisplayOrder,
            ])->values();
        }

        // If the user is authenticated, fetch all their leaderboard entries for the game.
        $userEntriesByLeaderboardId = collect();
        if ($user) {
            $leaderboardIds = $game->leaderboards->pluck('ID');
            $userEntries = LeaderboardEntry::whereIn('leaderboard_id', $leaderboardIds)
                ->where('user_id', $user->id)
                ->get();
            $userEntriesByLeaderboardId = $userEntries->keyBy('leaderboard_id');
        }

        if ($limit !== null) {
            $leaderboards = $leaderboards->take($limit);
        }

        return $leaderboards->map(function ($leaderboard) use ($userEntriesByLeaderboardId, $user) {
            // Build the user entry if it exists.
            $userEntryData = null;
            if ($user && $userEntriesByLeaderboardId->has($leaderboard->id)) {
                $userEntry = $userEntriesByLeaderboardId->get($leaderboard->id);
                $rank = $leaderboard->getRank($userEntry->score);

                $userEntryData = LeaderboardEntryData::fromLeaderboardEntry(
                    $userEntry,
                    $leaderboard->format,
                    $rank,
                )->include('formattedScore', 'rank');
            }

            return LeaderboardData::fromLeaderboard($leaderboard, $userEntryData)->include(
                'description',
                'format',
                'rankAsc',
                'title',
                'topEntry.formattedScore',
                'topEntry.user.avatarUrl',
                'topEntry.user.displayName',
                'userEntry',
                'state',
            );
        });
    }

    private function getLeaderboardsCount(Game $game, bool $isViewingPublishedAchievements): int
    {
        // Only count leaderboards if the system is active and it's not an event game.
        if (!$game->system->active || $game->system->id === System::Events) {
            return 0;
        }

        return $game->leaderboards
            ->where('state', $isViewingPublishedAchievements ? LeaderboardState::Active : LeaderboardState::Unpublished)
            ->count();
    }

    private function getInterestedDevelopersCount(Game $game, ?User $user): ?int
    {
        if (!$user || !$user->can('viewDeveloperInterest', $game)) {
            return null;
        }

        return UserGameListEntry::where('type', UserGameListType::Develop)
            ->where('GameID', $game->id)
            ->count();
    }

    private function getDefaultSort(Game $backingGame, ?PlayerGame $playerGame): GamePageListSort
    {
        // Derive the default sort based on user's unlock progress.
        // If the user has unlocked some (but not all) achievements, we can use the 'normal' sort order.
        // Otherwise, default to 'displayOrder' which is always available.
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
