<?php

declare(strict_types=1);

namespace App\Community\Actions;

use App\Enums\Permissions;
use App\Models\User;
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
    public function execute(): array
    {
        return Cache::flexible('all-active-players', [20, 60], function () {
            $recentMinutes = 5;
            $permissionsCutoff = Permissions::Registered;

            $timestampCutoff = Carbon::now()->subMinutes($recentMinutes);

            $activePlayers = User::select([
                "ID as user_id",
                "LastGameID",
                "User",
                "display_name",
                "RichPresenceMsg",
            ])
                ->with(['lastGame'])
                ->where("RichPresenceMsgDate", ">", $timestampCutoff)
                ->where("LastGameID", "<>", 0)
                ->where("Permissions", ">=", $permissionsCutoff)
                ->orderBy("Untracked", "asc")
                ->orderByDesc("RAPoints")
                ->orderByDesc("RASoftcorePoints")
                ->orderBy("ID", "asc")
                ->get();

            return $activePlayers->map(function ($player) {
                return [
                    'user_id' => $player->user_id,
                    'game_id' => $player->LastGameID,
                    'username' => $player->username,
                    'display_name' => $player->display_name,
                    'rich_presence' => $player->RichPresenceMsg,
                    'game_title' => $player->lastGame?->Title ?? '',
                ];
            })->toArray();
        });
    }
}