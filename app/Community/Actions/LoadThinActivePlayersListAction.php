<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Enums\Permissions;
use App\Models\GameRecentPlayer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class LoadThinActivePlayersListAction
{
    /**
     * @return array<array{
     *   user_id: int,
     *   game_id: int,
     *   username: string,
     *   display_name: string,
     *   rich_presence: string,
     *   game_title: string,
     * }>
     */
    public function execute(
        int $lookbackMinutes = 10,
        int $minimumPermissions = Permissions::Registered,
    ): array {
        return Cache::flexible(
            "all-active-players:{$lookbackMinutes}:{$minimumPermissions}",
            [20, 60],
            function () use ($lookbackMinutes, $minimumPermissions) {
                $timestampCutoff = Carbon::now()->subMinutes($lookbackMinutes);

                $activePlayers = GameRecentPlayer::with(['game'])
                    ->where('game_recent_players.rich_presence_updated_at', '>', $timestampCutoff)
                    ->join('users', 'users.id', '=', 'game_recent_players.user_id')
                    ->whereColumn('game_recent_players.game_id', 'users.rich_presence_game_id')
                    ->where('users.Permissions', '>=', $minimumPermissions)
                    ->whereNull('users.banned_at')
                    ->orderBy('users.Untracked', 'asc')
                    ->orderByDesc('users.points_hardcore')
                    ->orderByDesc('users.points')
                    ->orderBy('users.id', 'asc')
                    ->select(
                        'game_recent_players.*',
                        'users.username as username',
                        'users.display_name'
                    )
                    ->get();

                return $activePlayers->map(function ($player) {
                    return [
                        'user_id' => $player->user_id,
                        'game_id' => $player->game_id,
                        'username' => $player->username,
                        'display_name' => $player->display_name,
                        'rich_presence' => $player->rich_presence,
                        'game_title' => $player->game?->Title ?? '',
                    ];
                })->toArray();
            }
        );
    }
}
