<?php

declare(strict_types=1);

namespace App\Platform\Actions;

use App\Community\Enums\AwardType;
use App\Models\Game;
use App\Models\GameRecentPlayer;
use App\Models\PlayerBadge;
use App\Models\PlayerGame;
use App\Platform\Data\GameRecentPlayerData;
use App\Platform\Enums\UnlockMode;
use Illuminate\Support\Collection;

class LoadGameRecentPlayersAction
{
    /**
     * Load recent players for a game with all necessary data while avoiding N+1 queries.
     *
     * @return Collection<int, GameRecentPlayerData>
     */
    public function execute(Game $game, int $limit = 10): Collection
    {
        $recentPlayers = GameRecentPlayer::with('user')
            ->where('game_id', $game->id)
            ->orderBy('rich_presence_updated_at', 'DESC')
            ->limit($limit)
            ->get();

        if ($recentPlayers->isEmpty()) {
            return collect();
        }

        // Batch load all player games for these users and this game.
        $userIds = $recentPlayers->pluck('user_id')->toArray();
        $playerGames = PlayerGame::where('game_id', $game->id)
            ->whereIn('user_id', $userIds)
            ->get()
            ->keyBy('user_id');

        // Batch load all awards for these users for this specific game.
        $badges = PlayerBadge::whereIn('user_id', $userIds)
            ->where('AwardData', $game->id)
            ->whereIn('AwardType', [AwardType::Mastery, AwardType::GameBeaten])
            ->get()
            ->groupBy('user_id');

        return $recentPlayers->map(function (GameRecentPlayer $recentPlayer) use ($playerGames, $badges) {
            $playerGame = $playerGames->get($recentPlayer->user_id);

            // Find the highest award for this user and game.
            $userBadges = $badges->get($recentPlayer->user_id, collect());
            $highestAward = $this->getHighestAward($userBadges);

            return GameRecentPlayerData::fromGameRecentPlayer(
                $recentPlayer,
                $playerGame,
                $highestAward
            );
        });
    }

    /**
     * @param Collection<int, PlayerBadge> $badges
     */
    private function getHighestAward(Collection $badges): ?PlayerBadge
    {
        $awardPriority = [
            ['type' => AwardType::Mastery, 'extra' => UnlockMode::Hardcore],    // Mastery
            ['type' => AwardType::Mastery, 'extra' => UnlockMode::Softcore],    // Completion
            ['type' => AwardType::GameBeaten, 'extra' => UnlockMode::Hardcore], // Beaten
            ['type' => AwardType::GameBeaten, 'extra' => UnlockMode::Softcore], // Beaten (softcore)
        ];

        foreach ($awardPriority as $criteria) {
            $highestAward = $badges->first(function ($badge) use ($criteria) {
                return $badge->AwardType === $criteria['type'] && $badge->AwardDataExtra === $criteria['extra'];
            });

            if ($highestAward) {
                return $highestAward;
            }
        }

        return null;
    }
}
