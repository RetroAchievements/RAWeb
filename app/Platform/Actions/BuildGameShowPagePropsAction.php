<?php

declare(strict_types=1);

namespace App\Platform\Actions;

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
use App\Models\LeaderboardEntry;
use App\Models\PlayerGame;
use App\Models\Role;
use App\Models\System;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserBetaFeedbackSubmission;
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
use App\Platform\Data\PlayerGameData;
use App\Platform\Data\PlayerGameProgressionAwardsData;
use App\Platform\Data\UserCreditsData;
use App\Platform\Enums\AchievementAuthorTask;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementSetAuthorTask;
use App\Platform\Enums\AchievementSetType;
use App\Platform\Enums\GamePageListSort;
use App\Platform\Enums\GamePageListView;
use App\Support\Cache\CacheKey;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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
    ) {
    }

    public function execute(
        Game $game,
        ?User $user,
        AchievementFlag $targetAchievementFlag = AchievementFlag::OfficialCore,
        ?GameAchievementSet $targetAchievementSet = null,
        GamePageListView $initialView = GamePageListView::Achievements,
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
                $data = GameSetData::from($hub)->include('isEventHub');

                // Always remove updatedAt.
                $data = $data->except('updatedAt');

                // Remove isEventHub if it isn't true.
                if (!$hub->is_event_hub) {
                    $data = $data->except('isEventHub');
                }

                // Remove fields from hubs that don't have "Series" or "Meta|" in the title.
                if (!str_contains($hub->title, 'Series') && !str_contains($hub->title, 'Meta|')) {
                    $data = $data->except('badgeUrl', 'gameCount', 'linkCount', 'type');
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

        // Track the beta visit for authenticated users.
        $this->trackBetaVisit($user, 'react-game-page');

        // Get the primary claim from the already-loaded claims for permission checking.
        $primaryClaim = $backingGame->achievementSetClaims
            ->where('ClaimType', ClaimType::Primary)
            ->whereIn('Status', [ClaimStatus::Active, ClaimStatus::InReview])
            ->first();

        return new GameShowPagePropsData(
            achievementSetClaims: $achievementSetClaims,

            can: UserPermissionsData::fromUser($user, game: $backingGame, claim: $primaryClaim)->include(
                'createAchievementSetClaims',
                'createGameComments',
                'createGameForumTopic',
                'manageGames',
                'reviewAchievementSetClaims',
            ),

            canSubmitBetaFeedback: $this->getCanSubmitBetaFeedback($user, 'react-game-page'),
            initialSort: $this->getInitialSort($backingGame, $playerGame),
            initialView: $initialView,

            game: GameData::fromGame($game)->include(
                'achievementsPublished',
                'badgeUrl',
                'developer',
                'forumTopicId',
                'gameAchievementSets.achievementSet.achievements.description',
                'gameAchievementSets.achievementSet.achievements.developer',
                'gameAchievementSets.achievementSet.achievements.orderColumn',
                'gameAchievementSets.achievementSet.achievements.points',
                'gameAchievementSets.achievementSet.achievements.pointsWeighted',
                'gameAchievementSets.achievementSet.achievements.type',
                'gameAchievementSets.achievementSet.achievements.unlockedAt',
                'gameAchievementSets.achievementSet.achievements.unlockedHardcoreAt',
                'gameAchievementSets.achievementSet.achievements.unlockPercentage',
                'gameAchievementSets.achievementSet.achievements.unlocksHardcoreTotal',
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
                'guideUrl',
                'forumTopicId'
            ),

            claimData: $claimData,
            hasMatureContent: $backingGame->hasMatureContent,
            hubs: $relatedHubs,
            isOnWantToDevList: $initialUserGameListState['isOnWantToDevList'],
            isOnWantToPlayList: $initialUserGameListState['isOnWantToPlayList'],
            isSubscribedToComments: $user ? (new SubscriptionService())->isSubscribed($user, SubscriptionSubjectType::GameWall, $backingGame->id) : false,
            isLockedOnlyFilterEnabled: $isLockedOnlyFilterEnabled,
            isMissableOnlyFilterEnabled: $isMissableOnlyFilterEnabled,
            isViewingPublishedAchievements: $targetAchievementFlag === AchievementFlag::OfficialCore,
            followedPlayerCompletions: $this->buildFollowedPlayerCompletionAction->execute($user, $backingGame),

            leaderboards: request()->inertia()
                ? $this->buildLeaderboards($backingGame, $user)
                : Lazy::inertiaDeferred(fn () => $this->buildLeaderboards($backingGame, $user)),

            playerAchievementChartBuckets: $targetAchievementFlag === AchievementFlag::OfficialCore
                ? $this->buildGameAchievementDistributionAction->execute($backingGame, $user)
                : collect(),

            numComments: $backingGame->visibleComments($user)->count(),
            numCompatibleHashes: $this->getCompatibleHashesCount($game, $backingGame, $targetAchievementSet),
            numCompletions: $numCompletions,
            numBeaten: $numBeaten,
            numBeatenSoftcore: $numBeatenSoftcore,
            numLeaderboards: $this->getLeaderboardsCount($backingGame),
            numMasters: $numMasters,
            numOpenTickets: Ticket::forGame($backingGame)->unresolved()->count(),
            recentPlayers: $this->loadGameRecentPlayersAction->execute($game),
            recentVisibleComments: Collection::make(array_reverse(CommentData::fromCollection($backingGame->visibleComments))),
            topAchievers: $topAchievers,
            playerGame: $playerGame ? PlayerGameData::fromPlayerGame($playerGame) : null,
            playerGameProgressionAwards: $user
                ? PlayerGameProgressionAwardsData::fromArray(getUserGameProgressionAwards($backingGame->id, $user))
                : null,
            seriesHub: $this->buildSeriesHubDataAction->execute($game),
            setRequestData: $this->buildSetRequestData($backingGame, $user),
            targetAchievementSetId: $targetAchievementSet?->achievement_set_id,

            selectableGameAchievementSets: $game->getAttribute('selectableGameAchievementSets')
                ->map(function ($gas) {
                    $gas->achievementSet->setRelation('achievements', collect());

                    $gas->achievementSet->median_time_to_complete = $gas->achievementSet->median_time_to_complete ?? 0;
                    $gas->achievementSet->median_time_to_complete_hardcore = $gas->achievementSet->median_time_to_complete_hardcore ?? 0;

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
        );
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
            ->pluck('achievementSet.achievements.*.ID')
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
                ->whereIn('achievement_id', $achievementIds)
                ->where('is_active', true)
                ->select('user_id', DB::raw('COUNT(*) as count'), DB::raw('MAX(effective_from) as latest_date'))
                ->with('user')
                ->groupBy('user_id')
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
    private function buildLeaderboards(Game $game, ?User $user = null): Collection
    {
        // Only show leaderboards if the system is active and it's not an event game.
        if (!$game->system->active || $game->system->id === System::Events) {
            return collect();
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

        return $game->leaderboards->map(function ($leaderboard) use ($userEntriesByLeaderboardId, $user) {
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
            );
        });
    }

    private function getLeaderboardsCount(Game $game): int
    {
        // Only count leaderboards if the system is active and it's not an event game.
        if (!$game->system->active || $game->system->id === System::Events) {
            return 0;
        }

        return $game->leaderboards->count();
    }

    /**
     * @deprecated remove after the beta has ended
     */
    private function trackBetaVisit(?User $user, string $betaName): void
    {
        if (!$user) {
            return;
        }

        $cacheKey = CacheKey::buildUserBetaVisitsCacheKey($user->username, $betaName);

        // Get existing data, or initialize with new data.
        $data = Cache::get($cacheKey, [
            'visit_count' => 0,
            'first_visited_at' => now()->timestamp,
            'last_visited_at' => null,
        ]);

        // Update visit data.
        $data['visit_count']++;
        $data['last_visited_at'] = now()->timestamp;

        // Store for 30 days from now (the TTL resets on each visit).
        Cache::put($cacheKey, $data, Carbon::now()->addDays(30));
    }

    /**
     * @deprecated remove after the beta has ended
     */
    private function getCanSubmitBetaFeedback(?User $user, string $betaName): bool
    {
        if (!$user || !$user->can('create', UserBetaFeedbackSubmission::class)) {
            return false;
        }

        $cacheKey = CacheKey::buildUserBetaVisitsCacheKey($user->username, $betaName);
        $data = Cache::get($cacheKey);

        if (!$data) {
            return false;
        }

        // Check if user meets the requirements: 10+ visits over 2+ days.
        $requiredVisits = 10;
        $requiredDays = 2;

        $daysSinceFirst = $data['first_visited_at']
            ? Carbon::createFromTimestamp($data['first_visited_at'])->diffInDays(now())
            : 0;

        return $data['visit_count'] >= $requiredVisits && $daysSinceFirst >= $requiredDays;
    }

    private function getInitialSort(Game $backingGame, ?PlayerGame $playerGame): GamePageListSort
    {
        // Calculate the initial sort based on user's unlock progress.
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
}
