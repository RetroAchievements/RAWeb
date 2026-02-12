<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Community\Data\GameActivitySnapshotData;
use App\Community\Enums\GameActivitySnapshotType;
use App\Community\Enums\TrendingReason;
use App\Models\Event;
use App\Models\Game;
use App\Models\GameActivitySnapshot;
use App\Platform\Data\EventData;
use App\Platform\Data\GameData;
use Carbon\Carbon;
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
        $latestCreatedAt = GameActivitySnapshot::where('type', $type)->max('created_at');

        if (!$latestCreatedAt) {
            return collect();
        }

        return GameActivitySnapshot::where('type', $type)
            ->where('created_at', '>=', Carbon::parse($latestCreatedAt)->subSeconds(5))
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
        $isTrending = $type === GameActivitySnapshotType::Trending;

        // Batch-load events referenced in snapshot meta.
        $eventsById = $this->loadEventsFromMeta($snapshots);

        return Game::with('system')
            ->whereIn('id', $gameIds)
            ->get()
            ->map(function (Game $game) use ($snapshotsByGameId, $eventsById, $isTrending) {
                $gameData = GameData::from($game)->include(
                    'badgeUrl',
                    'system.iconUrl',
                    'system.nameShort',
                );

                $snapshot = $snapshotsByGameId[$game->id] ?? null;

                return new GameActivitySnapshotData(
                    game: $gameData,
                    playerCount: $isTrending ? 0 : ($snapshot?->player_count ?? 0),
                    trendingReason: $isTrending
                        ? TrendingReason::tryFrom($snapshot?->trending_reason ?? '')
                        : null,
                    event: $this->buildEventData($snapshot, $eventsById),
                );
            })
            ->sortByDesc(fn (GameActivitySnapshotData $data) => $snapshotsByGameId[$data->game->id]?->score ?? 0)
            ->values();
    }

    /**
     * @param array<int, Event> $eventsById
     */
    private function buildEventData(?GameActivitySnapshot $snapshot, array $eventsById): ?EventData
    {
        $eventId = $snapshot?->meta['event_id'] ?? null;
        if (!$eventId || !isset($eventsById[$eventId])) {
            return null;
        }

        return EventData::fromEvent($eventsById[$eventId])->include('legacyGame');
    }

    /**
     * @param Collection<int, GameActivitySnapshot> $snapshots
     * @return array<int, Event> keyed by event ID
     */
    private function loadEventsFromMeta(Collection $snapshots): array
    {
        $eventIds = $snapshots
            ->map(fn (GameActivitySnapshot $s) => $s->meta['event_id'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($eventIds)) {
            return [];
        }

        return Event::with('legacyGame')
            ->whereIn('id', $eventIds)
            ->get()
            ->keyBy('id')
            ->all();
    }
}
