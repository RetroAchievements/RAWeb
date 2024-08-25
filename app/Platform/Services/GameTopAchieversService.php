<?php

declare(strict_types=1);

namespace App\Platform\Services;

use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class GameTopAchieversService
{
    private int $gameId;
    private int $masteryPoints;
    private int $masteryAchievements;

    public function initialize(Game $game): void
    {
        $this->gameId = $game->id;
        $this->masteryAchievements = $game->achievements_published;
        $this->masteryPoints = $game->points_total;
    }

    /**
     * @return Builder<PlayerGame>
     */
    private function baseQuery(): Builder
    {
        return PlayerGame::where('game_id', $this->gameId)
            ->whereHas('user', function ($query) {
                return $query->whereNull('unranked_at')->where('Untracked', 0);
            });
    }

    /**
     * @return Builder<PlayerGame>
     */
    private function masteryQuery(): Builder
    {
        if ($this->masteryAchievements > 0) {
            return $this->baseQuery()->where('achievements_unlocked_hardcore', $this->masteryAchievements);
        } else {
            return $this->baseQuery()->where('achievements_unlocked_hardcore', '>', '0');
        }
    }

    public function numMasteries(): int
    {
        return $this->masteryQuery()->count();
    }

    /**
     * @return Collection<int, PlayerGame>
     */
    public function recentMasteries(int $count = 10): Collection
    {
        return $this->masteryQuery()->with('user')
            ->orderByDesc('last_unlock_hardcore_at')
            ->limit($count)
            ->get();
    }

    /**
     * @return Collection<int, PlayerGame>
     */
    public function allMasteries(int $offset = 0, int $count = 10): Collection
    {
        return $this->masteryQuery()->with('user')
            ->orderBy('last_unlock_hardcore_at')
            ->offset($offset)
            ->limit($count)
            ->get();
    }

    /**
     * @return Collection<int, PlayerGame>
     */
    public function highestPointEarners(int $count = 10): Collection
    {
        $query = $this->baseQuery()->where('achievements_unlocked_hardcore', '>', 0);

        if ($this->masteryPoints === 0) {
            // event with no points. primary sort by number of achievements unlocked.
            $query = $query->orderByDesc('achievements_unlocked_hardcore');
        } else {
            // standard game. primary sort by number of points earned.
            $query = $query->orderByDesc('points_hardcore');
        }

        return $query
            ->orderBy('last_unlock_hardcore_at')
            ->with('user')
            ->limit($count)
            ->get();
    }

    public static function expireTopAchieversComponentData(int $gameId): void
    {
        $cacheKey = "game:{$gameId}:top-achievers";
        Cache::forget($cacheKey);
    }

    public function getTopAchieversComponentData(): array
    {
        $cacheKey = "game:{$this->gameId}:top-achievers";
        $retval = Cache::get($cacheKey);
        if ($retval !== null) {
            $numTrashed = User::onlyTrashed()->whereIn('ID', array_column($retval[1], 'user_id'))->count();
            if ($numTrashed === 0) {
                return $retval;
            }
        }

        $numMasteries = $this->numMasteries();
        if ($numMasteries < 10) {
            return [
                $numMasteries,
                $this->convertPlayerGames($this->highestPointEarners()),
            ];
        }

        $retval = [
            $numMasteries,
            $this->convertPlayerGames($this->recentMasteries()),
        ];

        // only cache the result if the masters list is full.
        // that way we only have to expire it when there's a new mastery
        // or an achievement gets promoted or demoted
        Cache::put($cacheKey, $retval, Carbon::now()->addDays(30));

        return $retval;
    }

    /**
     * @param Collection<int, PlayerGame> $playerGames
     */
    private function convertPlayerGames(Collection $playerGames): array
    {
        $retval = [];
        foreach ($playerGames as $playerGame) {
            $retval[] = [
                'user_id' => $playerGame->user_id,
                'achievements_unlocked_hardcore' => $playerGame->achievements_unlocked_hardcore,
                'points_hardcore' => $playerGame->points_hardcore,
                'last_unlock_hardcore_at' => $playerGame->last_unlock_hardcore_at?->unix() ?? 0,
            ];
        }

        return $retval;
    }
}
