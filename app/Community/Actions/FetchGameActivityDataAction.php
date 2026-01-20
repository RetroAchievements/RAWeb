<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Data\GameActivitySnapshotData;
use App\Community\Enums\GameActivitySnapshotType;
use App\Community\Enums\TrendingReason;
use App\Models\Game;
use App\Models\GameActivitySnapshot;
use App\Platform\Data\GameData;
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

        $gameIds = $snapshots->pluck('game_id')->toArray();

        return $this->buildDataCollection($gameIds, $snapshots, $type);
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
            ->limit(4)
            ->get();
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
