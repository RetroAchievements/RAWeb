<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Data\CommentData;
use App\Community\Enums\ArticleType;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Community\Enums\UserGameListType;
use App\Data\UserPermissionsData;
use App\Enums\GameHashCompatibility;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserGameListEntry;
use App\Platform\Data\AchievementSetClaimData;
use App\Platform\Data\AggregateAchievementSetCreditsData;
use App\Platform\Data\GameAchievementSetData;
use App\Platform\Data\GameData;
use App\Platform\Data\GameSetData;
use App\Platform\Data\GameSetRequestData;
use App\Platform\Data\GameShowPagePropsData;
use App\Platform\Data\PlayerGameData;
use App\Platform\Data\PlayerGameProgressionAwardsData;
use App\Platform\Data\UserCreditsData;
use App\Platform\Enums\AchievementAuthorTask;
use App\Platform\Enums\AchievementSetAuthorTask;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;

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

    public function execute(Game $game, ?User $user, ?GameAchievementSet $targetAchievementSet = null): GameShowPagePropsData
    {
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
        $initialUserGameListState = $this->getInitialUserGameListState($game, $user);
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

        return new GameShowPagePropsData(
            achievementSetClaims: $achievementSetClaims,

            can: UserPermissionsData::fromUser($user, game: $game, claim: $primaryClaim)->include(
                'createAchievementSetClaims',
                'createGameComments',
                'createGameForumTopic',
                'manageGames',
                'reviewAchievementSetClaims',
            ),

            game: GameData::fromGame($game)->include(
                'achievementsPublished',
                'badgeUrl',
                'developer',
                'forumTopicId',
                'gameAchievementSets.achievementSet.achievements.description',
                'gameAchievementSets.achievementSet.achievements.developer',
                'gameAchievementSets.achievementSet.achievements.flags',
                'gameAchievementSets.achievementSet.achievements.orderColumn',
                'gameAchievementSets.achievementSet.achievements.points',
                'gameAchievementSets.achievementSet.achievements.pointsWeighted',
                'gameAchievementSets.achievementSet.achievements.type',
                'gameAchievementSets.achievementSet.achievements.unlockedAt',
                'gameAchievementSets.achievementSet.achievements.unlockedHardcoreAt',
                'gameAchievementSets.achievementSet.achievements.unlockHardcorePercentage',
                'gameAchievementSets.achievementSet.achievements.unlockPercentage',
                'gameAchievementSets.achievementSet.achievements.unlocksHardcoreTotal',
                'gameAchievementSets.achievementSet.achievements.unlocksTotal',
                'gameAchievementSets.achievementSet.medianTimeToComplete',
                'gameAchievementSets.achievementSet.medianTimeToCompleteHardcore',
                'gameAchievementSets.achievementSet.timesCompleted',
                'gameAchievementSets.achievementSet.timesCompletedHardcore',
                'genre',
                'guideUrl',
                'imageBoxArtUrl',
                'imageIngameUrl',
                'imageTitleUrl',
                'medianTimeToBeat',
                'medianTimeToBeatHardcore',
                'playersHardcore',
                'playersTotal',
                'pointsTotal',
                'publisher',
                'releasedAt',
                'releasedAtGranularity',
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
                'forumTopicId'
            ),

            claimData: $claimData,
            hasMatureContent: $backingGame->hasMatureContent,
            hubs: $relatedHubs,
            isOnWantToDevList: $initialUserGameListState['isOnWantToDevList'],
            isOnWantToPlayList: $initialUserGameListState['isOnWantToPlayList'],
            isSubscribedToComments: $user ? isUserSubscribedToArticleComments(ArticleType::Game, $backingGame->id, $user->id) : false,
            isLockedOnlyFilterEnabled: $isLockedOnlyFilterEnabled,
            isMissableOnlyFilterEnabled: $isMissableOnlyFilterEnabled,
            followedPlayerCompletions: $this->buildFollowedPlayerCompletionAction->execute($user, $backingGame),
            playerAchievementChartBuckets: $this->buildGameAchievementDistributionAction->execute($backingGame, $user),
            numComments: $backingGame->visibleComments($user)->count(),
            numCompatibleHashes: $this->getCompatibleHashesCount($game, $backingGame, $targetAchievementSet),
            numMasters: $numMasters,
            numCompletions: $numCompletions,
            numBeaten: $numBeaten,
            numBeatenSoftcore: $numBeatenSoftcore,
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

        // Process achievement authors and maintainers.
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

                // Count active maintainers.
                if ($achievement->activeMaintainer && $achievement->activeMaintainer->user) {
                    $userId = $achievement->activeMaintainer->user_id;
                    $existing = $achievementsMaintainers->get($userId);

                    $achievementsMaintainers->put($userId, [
                        'user' => $achievement->activeMaintainer->user,
                        'count' => ($existing['count'] ?? 0) + 1,
                        'created_at' => $achievement->activeMaintainer->effective_from,
                    ]);
                }
            }
        }

        // Process achievement authorship credits. We have numerous tasks at this level.
        foreach ($game->gameAchievementSets as $gameAchievementSet) {
            $achievementSet = $gameAchievementSet->achievementSet;

            foreach ($achievementSet->achievements as $achievement) {
                foreach ($achievement->authorshipCredits as $credit) {
                    $userId = $credit->user_id;
                    $user = $credit->user;

                    switch ($credit->task) {
                        case AchievementAuthorTask::Artwork->value:
                            $achievementsArtworkCredits->put($userId, [
                                'user' => $user,
                                'count' => ($achievementsArtworkCredits->get($userId)['count'] ?? 0) + 1,
                            ]);
                            break;

                        case AchievementAuthorTask::Design->value:
                            $achievementsDesignCredits->put($userId, [
                                'user' => $user,
                                'count' => ($achievementsDesignCredits->get($userId)['count'] ?? 0) + 1,
                            ]);
                            break;

                        case AchievementAuthorTask::Logic->value:
                            $achievementsLogicCredits->put($userId, [
                                'user' => $user,
                                'count' => ($achievementsLogicCredits->get($userId)['count'] ?? 0) + 1,
                            ]);
                            break;

                        case AchievementAuthorTask::Testing->value:
                            $achievementsTestingCredits->put($userId, [
                                'user' => $user,
                                'count' => ($achievementsTestingCredits->get($userId)['count'] ?? 0) + 1,
                            ]);
                            break;

                        case AchievementAuthorTask::Writing->value:
                            $achievementsWritingCredits->put($userId, [
                                'user' => $user,
                                'count' => ($achievementsWritingCredits->get($userId)['count'] ?? 0) + 1,
                            ]);
                            break;
                    }
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
}
