<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Data\CommentData;
use App\Community\Enums\ArticleType;
use App\Community\Enums\UserGameListType;
use App\Data\UserPermissionsData;
use App\Enums\GameHashCompatibility;
use App\Models\Game;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserGameListEntry;
use App\Platform\Data\AchievementSetClaimData;
use App\Platform\Data\AggregateAchievementSetCreditsData;
use App\Platform\Data\GameData;
use App\Platform\Data\GameSetData;
use App\Platform\Data\GameShowPagePropsData;
use App\Platform\Data\PlayerGameData;
use App\Platform\Data\PlayerGameProgressionAwardsData;
use App\Platform\Data\UserCreditsData;
use App\Platform\Enums\AchievementAuthorTask;
use App\Platform\Enums\AchievementSetAuthorTask;
use Illuminate\Support\Collection;

class BuildGameShowPagePropsAction
{
    public function __construct(
        protected BuildFollowedPlayerCompletionAction $buildFollowedPlayerCompletionAction,
        protected BuildGameAchievementDistributionAction $buildGameAchievementDistributionAction,
        protected LoadGameTopAchieversAction $loadGameTopAchieversAction,
        protected BuildSeriesHubDataAction $buildSeriesHubDataAction,
    ) {
    }

    public function execute(Game $game, ?User $user): GameShowPagePropsData
    {
        [$numMasters, $topAchievers] = $this->loadGameTopAchieversAction->execute($game);

        $playerGame = $user
            ? $user->playerGames()->whereGameId($game->id)->first()
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
            ->sortBy('sort_title');

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
            ->map(fn ($hub) => GameSetData::from($hub)->include('isEventHub'))
            ->values()
            ->all();

        $initialUserGameListState = $this->getInitialUserGameListState($game, $user);
        $achievementSetClaims = $this->buildAchievementSetClaims($game, $user);

        return new GameShowPagePropsData(
            achievementSetClaims: $achievementSetClaims,

            can: UserPermissionsData::fromUser($user, game: $game)->include(
                'createGameComments',
                'createGameForumTopic',
                'manageGames',
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
                'genre',
                'guideUrl',
                'imageBoxArtUrl',
                'imageIngameUrl',
                'imageTitleUrl',
                'playersHardcore',
                'pointsTotal',
                'publisher',
                'releasedAt',
                'releasedAtGranularity',
                'releases',
                'system.active',
                'system.iconUrl',
                'system.nameShort',
                'system',
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
            hubs: $relatedHubs,
            isOnWantToDevList: $initialUserGameListState['isOnWantToDevList'],
            isOnWantToPlayList: $initialUserGameListState['isOnWantToPlayList'],
            isSubscribedToComments: $user ? isUserSubscribedToArticleComments(ArticleType::Game, $game->id, $user->id) : false,
            followedPlayerCompletions: $this->buildFollowedPlayerCompletionAction->execute($user, $game),
            playerAchievementChartBuckets: $this->buildGameAchievementDistributionAction->execute($game, $user),
            numComments: $game->visibleComments($user)->count(),
            numCompatibleHashes: $game->hashes->where('compatibility', GameHashCompatibility::Compatible)->count(),
            numMasters: $numMasters,
            numOpenTickets: Ticket::forGame($game)->unresolved()->count(),
            recentVisibleComments: Collection::make(array_reverse(CommentData::fromCollection($game->visibleComments))),
            topAchievers: $topAchievers,
            playerGame: $playerGame ? PlayerGameData::fromPlayerGame($playerGame) : null,
            playerGameProgressionAwards: $user
                ? PlayerGameProgressionAwardsData::fromArray(getUserGameProgressionAwards($game->id, $user))
                : null,
            seriesHub: $this->buildSeriesHubDataAction->execute($game),
        );
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
            )->include('deletedAt'))
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

    /**
     * @return Collection<int, AchievementSetClaimData>
     */
    private function buildAchievementSetClaims(Game $game, ?User $user): Collection
    {
        // Build the include array based on current user permissions.
        $claimIncludes = ['user', 'finishedAt'];
        if ($user && $user->hasAnyRole([Role::DEV_COMPLIANCE, Role::MODERATOR, Role::ADMINISTRATOR])) {
            $claimIncludes[] = 'userLastPlayedAt';
        }

        return $game->achievementSetClaims
            ->map(fn ($claim) => AchievementSetClaimData::fromAchievementSetClaim($claim)->include(...$claimIncludes))
            ->values();
    }
}
