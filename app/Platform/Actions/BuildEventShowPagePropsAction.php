<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Data\UserPermissionsData;
use App\Models\Event;
use App\Models\User;
use App\Platform\Data\EventData;
use App\Platform\Data\EventShowPagePropsData;
use App\Platform\Data\GameSetData;
use App\Platform\Data\PlayerGameData;
use App\Platform\Data\PlayerGameProgressionAwardsData;

class BuildEventShowPagePropsAction
{
    public function __construct(
        protected BuildFollowedPlayerCompletionAction $buildFollowedPlayerCompletionAction,
        protected BuildGameAchievementDistributionAction $buildGameAchievementDistributionAction,
        protected LoadGameTopAchieversAction $loadGameTopAchieversAction,
    ) {
    }

    public function execute(
        Event $event,
        ?User $user,
    ): EventShowPagePropsData {
        [$numMasters, $topAchievers] = $this->loadGameTopAchieversAction->execute($event->legacyGame);

        $playerGame = $user
            ? $user->playerGames()->whereGameId($event->legacyGame->id)->first()
            : null;

            return new EventShowPagePropsData(
                can: UserPermissionsData::fromUser($user, game: $event->legacyGame)->include(
                    'createGameForumTopic',
                    'manageEvents'
                ),
                event: EventData::fromEvent($event)->include(
                    'eventAchievements.achievement.createdAt',
                    'eventAchievements.achievement.description',
                    'eventAchievements.achievement.orderColumn',
                    'eventAchievements.achievement.points',
                    'eventAchievements.achievement.unlockedAt',
                    'eventAchievements.achievement.unlockedHardcoreAt',
                    'eventAchievements.achievement.unlockHardcorePercentage',
                    'eventAchievements.achievement.unlockPercentage',
                    'eventAchievements.achievement.unlocksHardcoreTotal',
                    'eventAchievements.achievement.unlocksTotal',
                    'eventAchievements.achievement',
                    'eventAchievements.sourceAchievement.game',
                    'eventAchievements.sourceAchievement.game.system',
                    'eventAchievements.sourceAchievement.game.system.nameShort',
                    'eventAchievements.sourceAchievement.game.system.iconUrl',
                    'eventAchievements',
                    'eventAwards',
                    'eventAwards.badgeCount',
                    'legacyGame.achievementsPublished',
                    'legacyGame.badgeUrl',
                    'legacyGame.forumTopicId',
                    'legacyGame.imageBoxArtUrl',
                    'legacyGame.imageIngameUrl',
                    'legacyGame.imageTitleUrl',
                    'legacyGame.playersHardcore',
                    'legacyGame.playersTotal',
                    'legacyGame.pointsTotal',
                    'legacyGame',
                    'state',
                ),
                hubs: $event->legacyGame->hubs->map(fn ($hub) => GameSetData::from($hub))->all(),
                followedPlayerCompletions: $this->buildFollowedPlayerCompletionAction->execute($user, $event->legacyGame),
                playerAchievementChartBuckets: $this->buildGameAchievementDistributionAction->execute($event->legacyGame, $user),
                numMasters: $numMasters,
                topAchievers: $topAchievers,
                playerGame: $playerGame ? PlayerGameData::fromPlayerGame($playerGame) : null,
                playerGameProgressionAwards: $user
                    ? PlayerGameProgressionAwardsData::fromArray(getUserGameProgressionAwards($event->legacyGame->id, $user))
                    : null,
            );
    }
}
