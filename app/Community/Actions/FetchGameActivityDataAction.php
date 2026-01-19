<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Data\GameActivitySnapshotData;
use App\Community\Enums\GameActivitySnapshotType;
use App\Community\Enums\TrendingReason;
use App\Models\Game;
use App\Models\GameActivitySnapshot;
use App\Platform\Data\GameData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FetchGameActivityDataAction
{
    /**
     * @return Collection<int, GameActivitySnapshotData>
     */
    public function execute(GameActivitySnapshotType $type): Collection
    {
        $snapshots = $this->getLatestSnapshots($type);

        if ($snapshots->isEmpty()) {
            return collect();
        }

        $candidateGameIds = $snapshots->pluck('game_id')->toArray();
        $filteredGameIds = $this->filterMatureContent($candidateGameIds, limit: 4);

        return $this->buildDataCollection($filteredGameIds, $snapshots, $type);
    }

    /**
     * @return Collection<int, GameActivitySnapshot>
     */
    private function getLatestSnapshots(GameActivitySnapshotType $type): Collection
    {
        $query = GameActivitySnapshot::where('type', $type);

        $latestCreatedAt = $query->max('created_at');

        if (!$latestCreatedAt) {
            return collect();
        }

        return GameActivitySnapshot::where('type', $type)
            ->where('created_at', $latestCreatedAt)
            ->orderByDesc('score')
            ->limit(12)
            ->get();
    }

    /**
     * @param array<int> $gameIds
     * @return array<int>
     */
    private function filterMatureContent(array $gameIds, int $limit): array
    {
        if (empty($gameIds)) {
            return [];
        }

        $matureGameIds = Game::whereIn('id', $gameIds)
            ->whereHas('hubs', fn (Builder $query) => $query->where('has_mature_content', true))
            ->pluck('id')
            ->toArray();

        return array_slice(
            array_filter($gameIds, fn ($id) => !in_array($id, $matureGameIds)),
            0,
            $limit
        );
    }

    /**
     * @param array<int> $gameIds
     * @param Collection<int, GameActivitySnapshot> $snapshots
     * @return Collection<int, GameActivitySnapshotData>
     */
    private function buildDataCollection(
        array $gameIds,
        Collection $snapshots,
        GameActivitySnapshotType $type,
    ): Collection {
        $snapshotsByGameId = $snapshots->keyBy('game_id');

        return Game::with('system')
            ->whereIn('id', $gameIds)
            ->get()
            ->map(function (Game $game) use ($snapshotsByGameId, $type) {
                $gameData = GameData::from($game)->include(
                    'badgeUrl',
                    'system.iconUrl',
                    'system.nameShort',
                );

                $snapshot = $snapshotsByGameId[$game->id] ?? null;

                return new GameActivitySnapshotData(
                    game: $gameData,
                    playerCount: $type === GameActivitySnapshotType::Popular
                        ? ($snapshot?->player_count ?? 0)
                        : 0,
                    trendingReason: $type === GameActivitySnapshotType::Trending && $snapshot?->trending_reason
                        ? TrendingReason::tryFrom($snapshot->trending_reason)
                        : null,
                );
            })
            ->sortByDesc(fn (GameActivitySnapshotData $data) => $snapshotsByGameId[$data->game->id]?->score ?? 0)
            ->values();
    }
}
