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

        $entries = ConnectWarning::query()
            ->when($date != null, function ($query) use ($date) {
                // whereDate doesn't leverage index
                $query->where('created_at', '>=', $date->clone()->startOfDay())
                      ->where('created_at', '<=', $date->clone()->endOfDay());
            })
            ->when(filled($username), fn ($query) => $query->where('username', $username))
            ->with('playerSession', 'playerSession.gameHash')
            ->orderBy('created_at')
            ->get();
        foreach ($entries as $entry) {
            if (!in_array($entry->username, $usernames)) {
                $usernames[] = $entry->username;
            }

            $sniff = [
                'date' => $entry->created_at->format('Y-m-d H:i:s'),
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
                if (!in_array($entry->related_id, $achievementIds)) {
                    $achievementIds[] = $entry->related_id;
                }

                $sniff['achievementId'] = $entry->related_id;
                $sniff['hardcore'] = $entry->hardcore;

                $sniff['serverValidationHashes']['id_user_hardcore'] = md5($entry->related_id . $entry->username . ($entry->hardcore ? '1' : '0'));

                if ($entry->offset !== null) {
                    $sniff['serverValidationHashes']['id_user_hardcore_id_offset'] = md5($entry->related_id . $entry->username . ($entry->hardcore ? '1' : '0') . $entry->related_id . $entry->offset);
                }

            } elseif ($entry->related_type === 'leaderboard') {
                if (!in_array($entry->related_id, $leaderboardIds)) {
                    $leaderboardIds[] = $entry->related_id;
                }

                $sniff['leaderboardId'] = $entry->related_id;
                $sniff['score'] = $entry->extra;

                $sniff['serverValidationHashes']['id_user_score'] = md5($entry->related_id . $entry->username . $entry->extra);

                if ($entry->offset !== null) {
                    $sniff['serverValidationHashes']['id_user_score_offset'] = md5($entry->related_id . $entry->username . $entry->extra . $entry->offset);
                }
            }

            if ($entry->playerSession) {
                $sniff['gameHash'] = $entry->playerSession->gameHash?->md5;
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

        return array_reverse($sniffs); // newest first
    }
}
