<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Models\Game;
use App\Models\GameSet;
use App\Models\GameSetGame;
use App\Platform\Enums\GameSetType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttachGamesToHubsAction
{
    public const int MAX_GENRE_HUBS = 3;

    /**
     * @param Collection<int, GameSet>|GameSet[] $hubs
     * @param int[] $gameIds
     * @return array{attached: array<int, int[]>, skippedForGenreCap: list<array{hub_id: int, game_id: int}>}
     */
    public function execute(iterable $hubs, array $gameIds): array
    {
        $hubs = collect($hubs)->unique('id')->values();
        $gameIds = array_values(array_unique(array_map('intval', $gameIds)));

        if ($hubs->isEmpty() || empty($gameIds)) {
            return ['attached' => [], 'skippedForGenreCap' => []];
        }

        return DB::transaction(function () use ($hubs, $gameIds): array {
            Game::whereIn('id', $gameIds)->lockForUpdate()->get(['id']);

            $alreadyAttached = $this->findExistingAttachments($hubs->pluck('id')->all(), $gameIds);
            $genreHubTallies = $this->countGenreHubsPerGame($gameIds);

            $attached = [];
            $skippedForGenreCap = [];

            foreach ($hubs as $hub) {
                foreach ($gameIds as $gameId) {
                    if (isset($alreadyAttached[$hub->id][$gameId])) {
                        continue;
                    }

                    if ($hub->is_genre_hub) {
                        $tally = $genreHubTallies[$gameId] ?? 0;
                        if ($tally >= self::MAX_GENRE_HUBS) {
                            $skippedForGenreCap[] = ['hub_id' => $hub->id, 'game_id' => $gameId];
                            continue;
                        }

                        $genreHubTallies[$gameId] = $tally + 1;
                    }

                    $attached[$hub->id][] = $gameId;
                }
            }

            foreach ($hubs as $hub) {
                if (!empty($attached[$hub->id])) {
                    $hub->games()->attach($attached[$hub->id]);
                }
            }

            return ['attached' => $attached, 'skippedForGenreCap' => $skippedForGenreCap];
        });
    }

    /**
     * @param int[] $hubIds
     * @param int[] $gameIds
     * @return array<int, array<int, true>> [hub ID, [game ID, true]]
     */
    private function findExistingAttachments(array $hubIds, array $gameIds): array
    {
        $rows = GameSetGame::query()
            ->whereIn('game_set_id', $hubIds)
            ->whereIn('game_id', $gameIds)
            ->get(['game_set_id', 'game_id']);

        $existing = [];
        foreach ($rows as $row) {
            $existing[(int) $row->game_set_id][(int) $row->game_id] = true;
        }

        return $existing;
    }

    /**
     * @param int[] $gameIds
     * @return array<int, int> [game ID, number of genre hubs already attached]
     */
    private function countGenreHubsPerGame(array $gameIds): array
    {
        $rows = GameSetGame::query()
            ->whereIn('game_id', $gameIds)
            ->whereHas('gameSet', fn (Builder $query): Builder => $query->where('type', GameSetType::Hub))
            ->with(['gameSet' => fn (Relation $relation) => $relation->select(['id', 'type', 'title'])])
            ->get(['id', 'game_id', 'game_set_id']);

        $tallies = [];
        foreach ($rows as $row) {
            if ($row->gameSet?->is_genre_hub) {
                $tallies[(int) $row->game_id] = ($tallies[(int) $row->game_id] ?? 0) + 1;
            }
        }

        return $tallies;
    }
}
