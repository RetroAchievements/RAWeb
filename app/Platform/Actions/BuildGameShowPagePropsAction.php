<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Data\CommentData;
use App\Community\Enums\ArticleType;
use App\Data\UserPermissionsData;
use App\Enums\GameHashCompatibility;
use App\Models\Game;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Data\GameData;
use App\Platform\Data\GameSetData;
use App\Platform\Data\GameShowPagePropsData;
use App\Platform\Data\PlayerGameData;
use App\Platform\Data\PlayerGameProgressionAwardsData;
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

        return new GameShowPagePropsData(
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

            hubs: $relatedHubs,
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
}
