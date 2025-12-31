<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Connect\Support\BaseAuthenticatedApiAction;
use App\Enums\Permissions;
use App\Models\Game;
use App\Models\PlayerSession;
use Illuminate\Http\Request;

class GetFriendListAction extends BaseAuthenticatedApiAction
{
    public function execute(): array
    {
        return $this->process();
    }

    protected function initialize(Request $request): ?array
    {
        return null;
    }

    protected function process(): array
    {
        // only return the 100 most recently active friends (some users have more than 1000!)
        $friends = $this->user->followedUsers()
            ->where('users.Permissions', '>=', Permissions::Unregistered)
            ->whereNull('users.deleted_at')
            ->orderBy('users.last_activity_at', 'DESC')
            ->limit(100)
            ->select([
                'users.id',
                'users.username',
                'users.display_name',
                'users.points_hardcore',
                'users.rich_presence',
                'users.rich_presence_updated_at',
                'users.last_activity_at',
                'users.rich_presence_game_id',
            ])
            ->get();

        if ($friends->isEmpty()) {
            return [
                'Success' => true,
                'Friends' => [],
            ];
        }

        $friendIds = $friends->pluck('id')->toArray();

        // Get the most recent session date for each friend.
        $subQuery = PlayerSession::selectRaw('user_id, MAX(rich_presence_updated_at) as max_date')
            ->whereIn('user_id', $friendIds)
            ->whereNotNull('rich_presence_updated_at')
            ->groupBy('user_id');

        // Get full session details by joining with the subquery.
        $latestSessions = PlayerSession::from('player_sessions as ps')
            ->joinSub($subQuery, 'latest', function ($join) {
                $join
                    ->on('ps.user_id', '=', 'latest.user_id')
                    ->on('ps.rich_presence_updated_at', '=', 'latest.max_date');
            })
            ->with(['game:id,title,image_icon_asset_path'])
            ->select([
                'ps.user_id',
                'ps.rich_presence',
                'ps.rich_presence_updated_at',
                'ps.game_id',
            ])
            ->get()
            ->keyBy('user_id');

        // Get game IDs for users without recent sessions.
        $gameIds = $friends
            ->filter(function ($friend) use ($latestSessions) {
                return !isset($latestSessions[$friend->id]) && $friend->rich_presence_game_id > 0;
            })
            ->pluck('rich_presence_game_id')
            ->unique()
            ->toArray();

        // Get game data for missing games.
        $games = empty($gameIds)
            ? collect()
            : Game::query()
                ->whereIn('id', $gameIds)
                ->select(['id', 'title', 'image_icon_asset_path'])
                ->get()
                ->keyBy('id');

        $friendList = [];

        foreach ($friends as $friend) {
            $entry = [
                'Friend' => $friend->display_name,
                'AvatarUrl' => media_asset('UserPic/' . $friend->username . '.png'),
                'RAPoints' => $friend->points_hardcore,
                'LastSeen' => empty($friend->rich_presence) ? 'Unknown' : strip_tags($friend->rich_presence),
                'LastSeenTime' => ($friend->rich_presence_updated_at ?? $friend->last_activity_at)?->unix(),
            ];

            if (isset($latestSessions[$friend->id])) {
                $mostRecentSession = $latestSessions[$friend->id];
                $entry['LastSeen'] = $mostRecentSession->rich_presence;
                $entry['LastSeenTime'] = $mostRecentSession->rich_presence_updated_at->unix();

                if ($mostRecentSession->game) {
                    $entry['LastGameId'] = $mostRecentSession->game_id;
                    $entry['LastGameTitle'] = $mostRecentSession->game->title;
                    $entry['LastGameIconUrl'] = media_asset($mostRecentSession->game->image_icon_asset_path);
                } else {
                    $entry['LastGameId'] = $mostRecentSession->game_id;
                    $entry['LastGameTitle'] = null;
                    $entry['LastGameIconUrl'] = null;
                }
            } elseif ($friend->rich_presence_game_id && isset($games[$friend->rich_presence_game_id])) {
                $lastGame = $games[$friend->rich_presence_game_id];
                $entry['LastGameId'] = $lastGame->id;
                $entry['LastGameTitle'] = $lastGame->title;
                $entry['LastGameIconUrl'] = media_asset($lastGame->image_icon_asset_path);
            } else {
                $entry['LastGameId'] = null;
                $entry['LastGameTitle'] = null;
                $entry['LastGameIconUrl'] = null;
            }

            $friendList[] = $entry;
        }

        usort($friendList, function ($a, $b) {
            $diff = ($b['LastSeenTime'] ?? 0) - ($a['LastSeenTime'] ?? 0);
            if ($diff === 0) {
                $diff = $b['RAPoints'] - $a['RAPoints'];
            }

            return $diff;
        });

        return [
            'Success' => true,
            'Friends' => $friendList,
        ];
    }
}
