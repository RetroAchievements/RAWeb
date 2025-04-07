<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Data\UserPermissionsData;
use App\Models\Event;
use App\Models\PlayerBadge;
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

        // Some events have no promoted achievements and only have one award tier.
        // If this is the case, we need to make an extra query to get the real
        // mastery count of the event.
        if ($numMasters === 0 && !$event->awards->count()) {
            $numMasters = PlayerBadge::where('AwardType', AwardType::Event)
                ->where('AwardData', $event->id)
                ->count();
        }

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
