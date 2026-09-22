<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Achievement;
use App\Models\EventAchievement;
use App\Models\Game;
use App\Models\PlayerAchievement;
use App\Models\PlayerGame;
use App\Platform\Events\GamePlayerGameMetricsUpdated;
use App\Platform\Jobs\UpdateAchievementMetricsJob;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class BackfillEventAchievementUnlocksAction
{
    public const CHUNK_SIZE = 500;

    /**
     * Credits a chunk of the source achievement's winners to the target event achievement.
     *
     * The source row ID to resume after is returned (or null when the walk is finished and
     * the aggregates have fired). The caller is responsible for queueing the contiuation, so
     * the write and the recompute for a chunk always retry together.
     */
    public function execute(int $eventAchievementId, ?int $afterId = null): ?int
    {
        $eventAchievement = EventAchievement::query()
            ->with(['achievement.game.system', 'sourceAchievement'])
            ->find($eventAchievementId);

        $sourceAchievement = $eventAchievement?->sourceAchievement;
        $achievement = $eventAchievement?->achievement;

        if (!$eventAchievement || !$sourceAchievement || !$achievement?->is_promoted) {
            return null;
        }

        $winners = $this->findWinners($eventAchievement, $sourceAchievement, $afterId);

        /** @var array<int, string> $unlockedAtByUserId */
        $unlockedAtByUserId = [];
        foreach ($winners as $winner) {
            $unlockedAtByUserId[(int) $winner->user_id] = $winner->unlocked_hardcore_at->toDateTimeString();
        }

        $userIds = array_keys($unlockedAtByUserId);
        sort($userIds); // give concurrent chunks a consistent lock acquisition order

        $this->creditWinners($achievement, $userIds, $unlockedAtByUserId);
        $this->recomputePlayers($achievement->game, $userIds);

        if ($winners->count() === self::CHUNK_SIZE) {
            return (int) $winners->last()->id;
        }

        GamePlayerGameMetricsUpdated::dispatch($achievement->game);
        dispatch(new UpdateAchievementMetricsJob($achievement->id))->onQueue('achievement-metrics');

        return null;
    }

    /**
     * @return Collection<int, PlayerAchievement>
     */
    private function findWinners(
        EventAchievement $eventAchievement,
        Achievement $sourceAchievement,
        ?int $afterId,
    ): Collection {
        return PlayerAchievement::query()
            ->where('achievement_id', $sourceAchievement->id)
            ->whereNotNull('unlocked_hardcore_at')
            ->whereHas('user', fn (Builder $query): Builder => $query->whereNull('unranked_at'))
            ->when($eventAchievement->active_from, fn (Builder $query, Carbon $activeFrom): Builder => $query
                ->where('unlocked_hardcore_at', '>=', $activeFrom))
            ->when($eventAchievement->active_until, fn (Builder $query, Carbon $activeUntil): Builder => $query
                ->where('unlocked_hardcore_at', '<', $activeUntil))
            ->when($afterId !== null, fn (Builder $query): Builder => $query->where('player_achievements.id', '>', $afterId))
            ->orderBy('player_achievements.id')
            ->limit(self::CHUNK_SIZE)
            ->get([
                'player_achievements.id',
                'player_achievements.user_id',
                'player_achievements.unlocked_hardcore_at',
            ]);
    }

    /**
     * @param list<int> $userIds
     * @param array<int, string> $unlockedAtByUserId
     */
    private function creditWinners(Achievement $achievement, array $userIds, array $unlockedAtByUserId): void
    {
        if (!$userIds) {
            return;
        }

        $now = now()->toDateTimeString();

        DB::transaction(
            fn (): int => PlayerGame::query()->upsert(
                array_map(
                    fn (int $userId): array => [
                        'user_id' => $userId,
                        'game_id' => $achievement->game_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                        'deleted_at' => null,
                    ],
                    $userIds,
                ),
                ['user_id', 'game_id'],
                ['deleted_at'],
            ),
            attempts: 5,
        );

        DB::transaction(
            fn (): int => PlayerAchievement::query()->insertOrIgnore(array_map(
                fn (int $userId): array => [
                    'user_id' => $userId,
                    'achievement_id' => $achievement->id,
                    'unlocked_at' => $unlockedAtByUserId[$userId],
                    'unlocked_hardcore_at' => $unlockedAtByUserId[$userId],
                ],
                $userIds,
            )),
            attempts: 5,
        );

    }

    /**
     * @param list<int> $userIds
     */
    private function recomputePlayers(Game $game, array $userIds): void
    {
        if (!$userIds) {
            return;
        }

        $playerGames = PlayerGame::query()
            ->where('game_id', $game->id)
            ->whereIn('user_id', $userIds)
            ->with('user')
            ->get();

        foreach ($playerGames as $playerGame) {
            try {
                $playerGame->setRelation('game', $game); // don't keep repeatedly lazy loading the same game

                app()->make(UpdatePlayerGameMetricsAction::class)->execute($playerGame, silent: true);
            } catch (Throwable $e) {
                report($e); // don't bail the whole chunk if there's a single transient failure somewhere
            }
        }
    }
}
