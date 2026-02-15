<?php

declare(strict_types=1);

namespace App\Platform\Controllers;

use App\Community\Data\CommentData;
use App\Community\Enums\SubscriptionSubjectType;
use App\Community\Services\SubscriptionService;
use App\Data\UserPermissionsData;
use App\Http\Controller;
use App\Models\Achievement;
use App\Models\Game;
use App\Models\GameAchievementSet;
use App\Models\PlayerAchievement;
use App\Models\Role;
use App\Models\User;
use App\Platform\Data\AchievementData;
use App\Platform\Data\AchievementShowPagePropsData;
use App\Platform\Data\GameAchievementSetData;
use App\Platform\Data\GameData;
use App\Platform\Enums\AchievementSetType;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

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

        [$backingGame, $gameAchievementSet] = $this->resolveSubsetContext($achievement);

        // TODO $user conditional
        $playerAchievement = PlayerAchievement::where('user_id', $user->id)
            ->where('achievement_id', $achievement->id)
            ->first();

        [$proximityAchievements, $promotedAchievementCount] = $this->buildProximityAchievements($achievement, $user);

        $subscriptionService = new SubscriptionService();

        $props = new AchievementShowPagePropsData(
            achievement: AchievementData::fromAchievement($achievement, $playerAchievement)
                ->include(
                    'activeMaintainer',
                    'createdAt',
                    'description',
                    'developer',
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
                    'numUnresolvedTickets',
                ),
            can: UserPermissionsData::fromUser($user, triggerable: $achievement)
                ->include('createAchievementComments'),
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
            proximityAchievements: $proximityAchievements,
            promotedAchievementCount: $promotedAchievementCount,
        );

        return Inertia::render('achievement/[achievement]', $props);
    }

    /**
     * @return array{0: ?AchievementData[], 1: int}
     */
    private function buildProximityAchievements(Achievement $achievement, ?User $user): array
    {
        $achievementSet = $achievement->achievementSet;
        if (!$achievementSet) {
            return [null, 0];
        }

        // Get just the IDs of promoted achievements in set order.
        // We use DB::table() to avoid model bootstrapping overhead.
        $promotedIds = DB::table('achievement_set_achievements')
            ->join('achievements', 'achievements.id', '=', 'achievement_set_achievements.achievement_id')
            ->where('achievement_set_achievements.achievement_set_id', $achievementSet->id)
            ->where('achievements.is_promoted', true)
            ->orderBy('achievement_set_achievements.order_column')
            ->orderBy('achievement_set_achievements.created_at')
            ->pluck('achievement_set_achievements.achievement_id')
            ->all();

        $totalCount = count($promotedIds);
        if ($totalCount === 0) {
            return [null, 0];
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

        return [array_values(array_filter($proximityAchievementDtos)), $totalCount];
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
