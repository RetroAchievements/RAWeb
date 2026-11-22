<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Models\Achievement;
use App\Models\ConnectWarning;
use App\Models\Leaderboard;
use App\Models\User;
use App\Platform\Services\UserAgentService;
use Carbon\Carbon;

class BuildConnectSniffsAction
{
    public function execute(?Carbon $date, array &$clients, ?string $username = null): array
    {
        $sniffs = [];
        $usernames = [];
        $leaderboardIds = [];
        $achievementIds = [];
        $userAgentService = new UserAgentService();

        // Intentionally avoid hydrating models to conserve memory.
        $entries = ConnectWarning::query()
            ->leftJoin('player_sessions', 'player_sessions.id', '=', 'connect_warnings.player_session_id')
            ->leftJoin('game_hashes', 'game_hashes.id', '=', 'player_sessions.game_hash_id')
            ->when($date != null, function ($query) use ($date) {
                // whereDate doesn't leverage index
                $query->where('connect_warnings.created_at', '>=', $date->clone()->startOfDay())
                      ->where('connect_warnings.created_at', '<=', $date->clone()->endOfDay());
            })
            ->when(filled($username), fn ($query) => $query->where('connect_warnings.username', $username))
            ->select([
                'connect_warnings.created_at',
                'connect_warnings.username',
                'connect_warnings.method',
                'connect_warnings.smells',
                'connect_warnings.validation_hash',
                'connect_warnings.offset',
                'connect_warnings.related_type',
                'connect_warnings.related_id',
                'connect_warnings.hardcore',
                'connect_warnings.extra',
                'connect_warnings.user_agent',
                'player_sessions.id as joined_player_session_id',
                'game_hashes.md5 as game_hash_md5',
            ])
            ->orderByDesc('connect_warnings.created_at')
            ->orderByDesc('connect_warnings.id')
            ->toBase()
            ->cursor();
        foreach ($entries as $entry) {
            $usernames[$entry->username] = $entry->username;

            $sniff = [
                'date' => $entry->created_at,
                'user' => $entry->username,
                'method' => $entry->method,
                'smells' => [],
                'validationHash' => $entry->validation_hash,
                'serverValidationHashes' => [],
            ];

            foreach (explode(',', $entry->smells) as $smell) {
                $sniff['smells'][] = $smell;
            }

            if ($entry->offset !== null) {
                $sniff['offset'] = $entry->offset;
            }

            if ($entry->related_type === 'achievement') {
                $achievementIds[$entry->related_id] = $entry->related_id;

                $sniff['achievementId'] = $entry->related_id;
                $sniff['hardcore'] = $entry->hardcore;

                $sniff['serverValidationHashes']['id_user_hardcore'] = md5($entry->related_id . $entry->username . ($entry->hardcore ? '1' : '0'));

                if ($entry->offset !== null) {
                    $sniff['serverValidationHashes']['id_user_hardcore_id_offset'] = md5($entry->related_id . $entry->username . ($entry->hardcore ? '1' : '0') . $entry->related_id . $entry->offset);
                }

            } elseif ($entry->related_type === 'leaderboard') {
                $leaderboardIds[$entry->related_id] = $entry->related_id;

                $sniff['leaderboardId'] = $entry->related_id;
                $sniff['score'] = $entry->extra;

                $sniff['serverValidationHashes']['id_user_score'] = md5($entry->related_id . $entry->username . $entry->extra);

                if ($entry->offset !== null) {
                    $sniff['serverValidationHashes']['id_user_score_offset'] = md5($entry->related_id . $entry->username . $entry->extra . $entry->offset);
                }
            }

            if ($entry->joined_player_session_id !== null) {
                $sniff['gameHash'] = $entry->game_hash_md5;
            }

            $sniff['userAgent'] = $userAgent = $entry->user_agent;
            $unknown_client = null;
            if (empty($userAgent)) {
                $unknown_client = 'no_user_agent';
            } elseif (str_contains($userAgent, 'Mozilla')) {
                $unknown_client = 'browser';
            } elseif (in_array('unknown_client', $sniff['smells']) || in_array('blocked_client', $sniff['smells'])) {
                $data = $userAgentService->decode($userAgent);
                $unknown_client = $data['client'];
            }
            if ($unknown_client !== null) {
                $sniff['smells'][] = $unknown_client;
                if (!in_array($unknown_client, $clients)) {
                    $clients[] = $unknown_client;
                }
            }

            $sniffs[] = $sniff;
        }

        $userInfos = [];
        $users = User::query()
            ->whereIn('display_name', $usernames)
            ->orWhereIn('username', $usernames)
            ->withTrashed()
            ->select('id', 'username', 'display_name', 'Permissions', 'deleted_at', 'unranked_at')
            ->toBase()
            ->get()
            ->toArray();
        foreach ($users as $user) {
            $userInfos[strtolower($user->username)] = $user;
            $userInfos[strtolower($user->display_name)] = $user;
        }

        $achievements = Achievement::query()
            ->whereIn('id', $achievementIds)
            ->select('id', 'title')
            ->toBase()
            ->get()
            ->keyBy('id')
            ->toArray();

        $leaderboards = Leaderboard::query()
            ->whereIn('id', $leaderboardIds)
            ->select('id', 'title')
            ->toBase()
            ->get()
            ->keyBy('id')
            ->toArray();

        foreach ($sniffs as &$sniff) {
            $lowerUsername = strtolower($sniff['user']);
            if (array_key_exists($lowerUsername, $userInfos)) {
                $sniff['userinfo'] = $userInfos[$lowerUsername];
                $linkUsername = $sniff['userinfo']->display_name;
            } elseif (empty($lowerUsername)) {
                $sniff['smells'][] = 'no_user';
                $linkUsername = '';
            } else {
                $sniff['smells'][] = 'unknown_user';
                $linkUsername = $sniff['user'];
            }

            $sniff['link'] = empty($linkUsername) ? '' : "historyexamine.php?u={$linkUsername}&d=" . strtotime($sniff['date']);

            if (array_key_exists('achievementId', $sniff) && array_key_exists($sniff['achievementId'], $achievements)) {
                $sniff['achievement'] = $achievements[$sniff['achievementId']];
            }

            if (array_key_exists('leaderboardId', $sniff) && array_key_exists($sniff['leaderboardId'], $leaderboards)) {
                $sniff['leaderboard'] = $leaderboards[$sniff['leaderboardId']];
            }
        }

        return $sniffs;
    }
}
