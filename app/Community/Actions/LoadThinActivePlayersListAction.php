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
        int $minimumPermissions = Permissions::Registered
    ): array {
        return Cache::flexible(
            "all-active-players:{$lookbackMinutes}:{$minimumPermissions}",
            [20, 60],
            function () use ($lookbackMinutes, $minimumPermissions) {
                $timestampCutoff = Carbon::now()->subMinutes($lookbackMinutes);

                $activePlayers = GameRecentPlayer::with(['game'])
                    ->where('rich_presence_updated_at', '>', $timestampCutoff)
                    ->join('UserAccounts', 'UserAccounts.ID', '=', 'game_recent_players.user_id')
                    ->where('UserAccounts.Permissions', '>=', $minimumPermissions)
                    ->whereNull('UserAccounts.banned_at')
                    ->orderBy('UserAccounts.Untracked', 'asc')
                    ->orderByDesc('UserAccounts.RAPoints')
                    ->orderByDesc('UserAccounts.RASoftcorePoints')
                    ->orderBy('UserAccounts.ID', 'asc')
                    ->select(
                        'game_recent_players.*',
                        'UserAccounts.User as username',
                        'UserAccounts.display_name'
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
