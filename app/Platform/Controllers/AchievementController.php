<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Data\CommentData;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Services\SubscriptionService;
use App\Data\UserData;
use App\Data\UserPermissionsData;
use App\Http\Controller;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\PlayerAchievement;
use App\Models\Role;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\BuildAchievementChangelogAction;
use App\Platform\Data\AchievementData;
use App\Platform\Data\AchievementRecentUnlockData;
use App\Platform\Data\AchievementShowPagePropsData;
use App\Platform\Data\EventAchievementData;
use App\Platform\Data\GameAchievementSetData;
use App\Platform\Data\GameData;
use App\Platform\Enums\AchievementPageTab;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Spatie\LaravelData\Lazy;

class AchievementController extends Controller
{
    protected function resourceName(): string
    {
        return 'achievement';
    }

    public function show(Request $request, Achievement $achievement): InertiaResponse
    {
        $this->authorize('view', $achievement);

        // TODO remove when starting beta
        /** @var ?User $user */
        $user = $request->user();
        if (!$user || !$user->hasAnyRole([Role::ADMINISTRATOR, Role::MODERATOR])) {
            abort(404);
        }
        // ENDTODO

        $achievement->loadMissing([
            'achievementSet',
            'activeMaintainer.user',
            'developer',
            'game.system',
            'visibleComments' => function ($query) {
                $query->notAutomated()
                    ->latest('created_at')
                    ->limit(20)
                    ->with(['user' => fn ($q) => $q->withTrashed()]);
            },
        ]);

        $isEventGame = $achievement->game->system_id === System::Events;

        if ($isEventGame) {
            $achievement->loadMissing('eventData.sourceAchievement.game.system');
        }

        [$backingGame, $gameAchievementSet] = $this->resolveSubsetContext($achievement);

        // TODO $user conditional
        $playerAchievement = PlayerAchievement::where('user_id', $user->id)
            ->where('achievement_id', $achievement->id)
            ->first();

        ['achievements' => $proximityAchievements, 'totalCount' => $promotedAchievementCount, 'areAllOnePoint' => $areAllOnePoint]
            = $this->buildProximityAchievements($achievement, $user);

        // Build event-specific data if this achievement belongs to an event game.
        $eventAchievementData = null;
        $achievementData = AchievementData::fromAchievement($achievement, $playerAchievement);

        if ($isEventGame && $achievement->eventData) {
            $eventAchievementData = EventAchievementData::fromEventAchievement($achievement->eventData)
                ->include(
                    'sourceAchievement',
                    'sourceAchievement.game',
                    'sourceAchievement.game.badgeUrl',
                    'sourceAchievement.game.system',
                    'sourceAchievement.game.system.iconUrl',
                    'sourceAchievement.game.system.nameShort',
                    'activeFrom',
                    'activeThrough',
                );

            // When the event achievement is obfuscated (upcoming achievement),
            // use the scrubbed achievement data so the real details aren't leaked.
            if ($eventAchievementData->isObfuscated) {
                $achievementData = $eventAchievementData->achievement;
            }
        }

        $defaultTab = $isEventGame ? AchievementPageTab::Unlocks : AchievementPageTab::Comments;
        $initialTab = AchievementPageTab::tryFrom($request->query('tab', '')) ?? $defaultTab;

        $subscriptionService = new SubscriptionService();
        $changelog = (new BuildAchievementChangelogAction())->execute($achievement);

        $props = new AchievementShowPagePropsData(
            achievement: $achievementData
                ->include(
                    'activeMaintainer',
                    'createdAt',
                    'description',
                    'developer',
                    'embedUrl',
                    'game',
                    'game.badgeUrl',
                    'game.playersTotal',
                    'game.system',
                    'game.system.iconUrl',
                    'game.system.nameShort',
                    'modifiedAt',
                    'points',
                    'pointsWeighted',
                    'type',
                    'unlockedAt',
                    'unlockedHardcoreAt',
                    'unlockPercentage',
                    'unlocksHardcore',
                    'unlocksTotal',
                    'isPromoted',
                    'numUnresolvedTickets',
                ),
            can: UserPermissionsData::fromUser($user, triggerable: $achievement)
                ->include(
                    'createAchievementComments',
                    'develop',
                    'updateAchievementDescription',
                    'updateAchievementIsPromoted',
                    'updateAchievementPoints',
                    'updateAchievementTitle',
                    'updateAchievementType',
                    'viewAchievementLogic',
                ),
            isSubscribedToComments: $subscriptionService->isSubscribed($user, SubscriptionSubjectType::Achievement, $achievement->id), // TODO $user conditional
            numComments: $achievement->visibleComments($user)->notAutomated()->count(),
            recentVisibleComments: Collection::make(array_reverse(
                CommentData::fromCollection($achievement->visibleComments)
            )),
            backingGame: $backingGame
                ? GameData::fromGame($backingGame)->include('badgeUrl', 'system')
                : null,
            gameAchievementSet: $gameAchievementSet
                ? GameAchievementSetData::from($gameAchievementSet)->include('type', 'title', 'achievementSet.imageAssetPathUrl')
                : null,
            changelog: $changelog,
            proximityAchievements: $proximityAchievements,
            promotedAchievementCount: $promotedAchievementCount,
            recentUnlocks: $this->buildRecentUnlocks($achievement, $isEventGame),
            initialTab: $initialTab,
            eventAchievement: $eventAchievementData,
            isEventGame: $isEventGame,
            areAllAchievementsOnePoint: $areAllOnePoint,
        );

        return Inertia::render('achievement/[achievement]', $props);
    }

    /**
     * Event achievements always show the unlocks by default, so the data
     * must be available on initial render rather than via a deferred partial.
     */
    private function buildRecentUnlocks(Achievement $achievement, bool $isEventGame): Lazy
    {
        $query = fn () => PlayerAchievement::with('user')
            ->whereHas('user')
            ->where('achievement_id', $achievement->id)
            ->ranked()
            ->orderByDesc('unlocked_effective_at')
            ->limit(50)
            ->get()
            ->map(fn ($pa) => new AchievementRecentUnlockData(
                user: UserData::fromUser($pa->user)->include('displayName', 'avatarUrl'),
                unlockedAt: $pa->unlocked_effective_at,
                isHardcore: $pa->unlocked_hardcore_at !== null,
            ));

        /**
         * Lazy::create()->defaultIncluded() bypasses the #[AutoInertiaDeferred]
         * attribute on the DTO prop while still being a Lazy instance. This
         * ensures the data is included in the initial page payload.
         */
        if ($isEventGame) {
            return Lazy::create($query)->defaultIncluded();
        }

        return Lazy::inertiaDeferred($query);
    }

    /**
     * @return array{achievements: ?AchievementData[], totalCount: int, areAllOnePoint: bool}
     */
    private function buildProximityAchievements(Achievement $achievement, ?User $user): array
    {
        $achievementSet = $achievement->achievementSet;
        if (!$achievementSet) {
            return ['achievements' => null, 'totalCount' => 0, 'areAllOnePoint' => false];
        }

        // Get the IDs and points of promoted achievements in set order.
        // We use DB::table() to avoid model bootstrapping overhead.
        $promoted = DB::table('achievement_set_achievements')
            ->join('achievements', 'achievements.id', '=', 'achievement_set_achievements.achievement_id')
            ->where('achievement_set_achievements.achievement_set_id', $achievementSet->id)
            ->where('achievements.is_promoted', true)
            ->orderBy('achievement_set_achievements.order_column')
            ->orderBy('achievement_set_achievements.created_at')
            ->select('achievement_set_achievements.achievement_id', 'achievements.points')
            ->get();

        $promotedIds = $promoted->pluck('achievement_id')->all();
        $areAllOnePoint = $promoted->isNotEmpty() && $promoted->every(fn ($row) => (int) $row->points === 1);

        $totalCount = count($promotedIds);
        if ($totalCount === 0) {
            return ['achievements' => null, 'totalCount' => 0, 'areAllOnePoint' => $areAllOnePoint];
        }

        $windowIds = $this->resolveProximityWindow($achievement->id, $promotedIds);

        $achievements = Achievement::whereIn('id', $windowIds)->get()->keyBy('id');

        $playerAchievements = $user
            ? PlayerAchievement::where('user_id', $user->id)
                ->whereIn('achievement_id', $windowIds)
                ->get()
                ->keyBy('achievement_id')
            : collect();

        $proximityAchievementDtos = array_map(function ($id) use ($achievements, $playerAchievements) {
            $proximityAchievement = $achievements->get($id);
            if (!$proximityAchievement) {
                return null;
            }

            return AchievementData::fromAchievement($proximityAchievement, $playerAchievements[$id] ?? null)
                ->include('description', 'points', 'unlockPercentage', 'unlockedAt', 'unlockedHardcoreAt');
        }, $windowIds);

        return [
            'achievements' => array_values(array_filter($proximityAchievementDtos)),
            'totalCount' => $totalCount,
            'areAllOnePoint' => $areAllOnePoint,
        ];
    }

    /**
     * Determine which slice of nearby achievements to show around the current achievement.
     *
     * @param int[] $promotedIds
     * @return int[]
     */
    private function resolveProximityWindow(int $achievementId, array $promotedIds): array
    {
        $windowSize = 11;
        $totalCount = count($promotedIds);

        if ($totalCount <= $windowSize) {
            return $promotedIds;
        }

        $currentIndex = array_search($achievementId, $promotedIds);

        // If the current achievement isn't promoted, then show the first N promoted achievements.
        if ($currentIndex === false) {
            return array_slice($promotedIds, 0, $windowSize);
        }

        // As best as we can, center the current achievement in the window.
        $halfWindow = intdiv($windowSize, 2);
        $windowStart = max(0, min($currentIndex - $halfWindow, $totalCount - $windowSize));

        return array_slice($promotedIds, $windowStart, $windowSize);
    }

    /**
     * @return array{0: ?Game, 1: ?GameAchievementSet}
     */
    private function resolveSubsetContext(Achievement $achievement): array
    {
        $game = $achievement->game;

        $backingGame = $game->parentGame();
        if (!$backingGame) {
            return [null, null];
        }

        $backingGame->loadMissing('system');

        // Find the GameAchievementSet that links this subset's core set to the backing game.
        $coreSetId = $game->gameAchievementSets()->core()->value('achievement_set_id');

        $gameAchievementSet = $coreSetId
            ? GameAchievementSet::with('achievementSet')
                ->where('game_id', $backingGame->id)
                ->where('achievement_set_id', $coreSetId)
                ->where('type', '!=', AchievementSetType::Core)
                ->first()
            : null;

        if ($gameAchievementSet) {
            // These fields may be null in the database but are required by the DTO.
            $gameAchievementSet->achievementSet->median_time_to_complete ??= 0;
            $gameAchievementSet->achievementSet->median_time_to_complete_hardcore ??= 0;
            $gameAchievementSet->achievementSet->players_hardcore ??= 0;
            $gameAchievementSet->achievementSet->players_total ??= 0;
        }

        return [$backingGame, $gameAchievementSet];
    }
}
