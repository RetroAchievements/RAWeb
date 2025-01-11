<?php

declare(strict_types=1);

namespace App\Platform\Services\GameSuggestions\Strategies;

use App\Models\Game;
use App\Models\PlayerGame;
use App\Models\User;
use App\Platform\Data\GameData;
use App\Platform\Data\GameSuggestionContextData;
use App\Platform\Enums\GameSuggestionReason;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CommonPlayersStrategy implements GameSuggestionStrategy
{
    public function __construct(
        private readonly User $user,
        private readonly Game $sourceGame,
        private readonly bool $attachContext = true,
    ) {
    }

    public function select(): ?Game
    {
        // Get a pseudorandom sample of users who mastered the source game.
        // Note that this random sample will always be the same, we're sampling using a modulo operator.
        $masterUserIds = $this->getMasterUserIds();

        if ($masterUserIds->isEmpty()) {
            return null;
        }

        // Find most common games mastered by these users.
        $gameId = PlayerGame::select('game_id')
            ->whereIn('user_id', $masterUserIds)
            ->whereAllAchievementsUnlocked()
            ->where('achievements_total', '>', 0)
            ->where('game_id', '!=', $this->sourceGame->id)
            ->whereNull('deleted_at')
            ->groupBy('game_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(10)
            ->value('game_id');

        return $gameId ? Game::find($gameId) : null;
    }

    public function reason(): GameSuggestionReason
    {
        return GameSuggestionReason::CommonPlayers;
    }

    public function reasonContext(): ?GameSuggestionContextData
    {
        if (!$this->attachContext) {
            return null;
        }

        return GameSuggestionContextData::forCommonPlayersGame(
            GameData::from($this->sourceGame)->include('badgeUrl')
        );
    }

    /**
     * @return Collection<int, int>
     */
    private function getMasterUserIds(): Collection
    {
        $connection = DB::connection()->getDriverName();

        return match ($connection) {
            'sqlite' => $this->getMasterUserIdsSQLite(),
            default => $this->getMasterUserIdsMariaDB(),
        };
    }

    /**
     * @return Collection<int, int>
     */
    private function getMasterUserIdsMariaDB(): Collection
    {
        // Use the optimized index hint for MariaDB.
        // This is unfortunately not supported by SQLite.
        return PlayerGame::from(DB::raw('`player_games` FORCE INDEX (player_games_completion_sample_idx)'))
            ->where('game_id', $this->sourceGame->id)
            ->where('user_id', '!=', $this->user->id)
            ->whereAllAchievementsUnlocked()
            ->whereNull('deleted_at')
            ->whereRaw('id % 100 < 5')
            ->limit(5)
            ->pluck('user_id');
    }

    /**
     * @return Collection<int, int>
     */
    private function getMasterUserIdsSQLite(): Collection
    {
        // For SQLite, we'll use a different approach to get a stable random sample.
        // We use the rowid (which SQLite automatically provides) for sampling.
        return PlayerGame::where('game_id', $this->sourceGame->id)
            ->where('user_id', '!=', $this->user->id)
            ->whereAllAchievementsUnlocked()
            ->whereNull('deleted_at')
            ->orderByRaw('rowid % 100')
            ->limit(5)
            ->pluck('user_id');
    }
}
