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
            ->where('UserAccounts.Permissions', '>=', Permissions::Unregistered)
            ->whereNull('UserAccounts.Deleted')
            ->orderBy('UserAccounts.LastLogin', 'DESC')
            ->limit(100)
            ->select([
                'UserAccounts.ID',
                'UserAccounts.User',
                'UserAccounts.display_name',
                'UserAccounts.RAPoints',
                'UserAccounts.RichPresenceMsg',
                'UserAccounts.RichPresenceMsgDate',
                'UserAccounts.LastLogin',
                'UserAccounts.LastGameID',
            ])
            ->get();

        if ($friends->isEmpty()) {
            return [
                'Success' => true,
                'Friends' => [],
            ];
        }

        $friendIds = $friends->pluck('ID')->toArray();

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
            ->with(['game:ID,Title,ImageIcon'])
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
                return !isset($latestSessions[$friend->ID]) && $friend->LastGameID > 0;
            })
            ->pluck('LastGameID')
            ->unique()
            ->toArray();

        // Get game data for missing games.
        $games = empty($gameIds)
            ? collect()
            : Game::query()
                ->whereIn('ID', $gameIds)
                ->select(['ID', 'Title', 'ImageIcon'])
                ->get()
                ->keyBy('ID');

        $friendList = [];

        foreach ($friends as $friend) {
            $entry = [
                'Friend' => $friend->display_name,
                'AvatarUrl' => media_asset('UserPic/' . $friend->User . '.png'),
                'RAPoints' => $friend->RAPoints,
                'LastSeen' => empty($friend->RichPresenceMsg) ? 'Unknown' : strip_tags($friend->RichPresenceMsg),
                'LastSeenTime' => ($friend->RichPresenceMsgDate ?? $friend->LastLogin)?->unix(),
            ];

            if (isset($latestSessions[$friend->id])) {
                $mostRecentSession = $latestSessions[$friend->id];
                $entry['LastSeen'] = $mostRecentSession->rich_presence;
                $entry['LastSeenTime'] = $mostRecentSession->rich_presence_updated_at->unix();

                if ($mostRecentSession->game) {
                    $entry['LastGameId'] = $mostRecentSession->game_id;
                    $entry['LastGameTitle'] = $mostRecentSession->game->title;
                    $entry['LastGameIconUrl'] = media_asset($mostRecentSession->game->ImageIcon);
                } else {
                    $entry['LastGameId'] = $mostRecentSession->game_id;
                    $entry['LastGameTitle'] = null;
                    $entry['LastGameIconUrl'] = null;
                }
            } elseif ($friend->LastGameID && isset($games[$friend->LastGameID])) {
                $lastGame = $games[$friend->LastGameID];
                $entry['LastGameId'] = $lastGame->id;
                $entry['LastGameTitle'] = $lastGame->title;
                $entry['LastGameIconUrl'] = media_asset($lastGame->ImageIcon);
            } else {
                $entry['LastGameId'] = null;
                $entry['LastGameTitle'] = null;
                $entry['LastGameIconUrl'] = null;
            }

            $friendList[] = $entry;
        }

        usort($friendList, function ($a, $b) {
            $diff = $b['LastSeenTime'] - $a['LastSeenTime'];
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
