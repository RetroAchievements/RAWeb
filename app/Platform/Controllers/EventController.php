<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Data\UserPermissionsData;
use App\Http\Controller;
use App\Models\Event;
use App\Models\User;
use App\Platform\Actions\BuildFollowedPlayerCompletionAction;
use App\Platform\Actions\BuildGameAchievementDistributionAction;
use App\Platform\Data\EventData;
use App\Platform\Data\EventShowPagePropsData;
use App\Platform\Data\GameSetData;
use App\Platform\Data\GameTopAchieverData;
use App\Platform\Data\PlayerGameData;
use App\Platform\Data\PlayerGameProgressionAwardsData;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Services\GameTopAchieversService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class EventController extends Controller
{
    public function show(
        Request $request,
        Event $event,
        GameTopAchieversService $gameTopAchieversService,
    ): InertiaResponse {
        $this->authorize('view', $event);

        /** @var ?User $user */
        $user = $request->user();

        $event->loadMissing([
            'legacyGame',
            'achievements' => function ($query) use ($user) {
                $query->with(['sourceAchievement.game.system'])
                    ->where('Flags', AchievementFlag::OfficialCore->value);

                if ($user) {
                    $query->with(['achievement.playerAchievements' => function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    }]);
                }
            },
            'awards' => function ($query) use ($user, $event) {
                $query->withCount('playerBadges as badge_count');

                if ($user) {
                    $query->with(['playerBadges' => function ($query) use ($user, $event) {
                        $query
                            ->where('user_id', $user->id)
                            ->where('AwardData', $event->id);
                    }]);
                }
            },
        ]);

        $gameTopAchieversService->initialize($event->legacyGame);
        [$numMasters, $rawTopAchievers] = $gameTopAchieversService->getTopAchieversComponentData();

        /** @var array<int, array<string, mixed>> $rawTopAchievers */
        /** @var Collection<int, GameTopAchieverData> $topAchievers */
        $topAchievers = collect($rawTopAchievers)
            ->map(function (array $topAchiever): GameTopAchieverData {
                return GameTopAchieverData::fromTopAchiever($topAchiever);
            });

        $playerGame = $user
            ? $user->playerGames()->whereGameId($event->legacyGame->id)->first()
            : null;

        $props = new EventShowPagePropsData(
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
            followedPlayerCompletions: (new BuildFollowedPlayerCompletionAction())->execute($user, $event->legacyGame),
            playerAchievementChartBuckets: (new BuildGameAchievementDistributionAction())->execute($event->legacyGame, $user),
            numMasters: $numMasters,
            topAchievers: $topAchievers,
            playerGame: $playerGame ? PlayerGameData::fromPlayerGame($playerGame) : null,
            playerGameProgressionAwards: $user
                ? PlayerGameProgressionAwardsData::fromArray(getUserGameProgressionAwards($event->legacyGame->id, $user))
                : null,
        );

        return Inertia::render('event/[event]', $props);
    }
}
