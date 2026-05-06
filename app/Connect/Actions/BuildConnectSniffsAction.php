<?php

declare(strict_types=1);

namespace App\Connect\Actions;

use App\Models\ConnectWarning;
use App\Models\Leaderboard;
use App\Models\User;
use App\Platform\Services\UserAgentService;
use Carbon\Carbon;

class BuildConnectSniffsAction
{
    public function execute(Carbon $date, array &$clients): array
    {
        $sniffs = [];
        $usernames = [];
        $leaderboardIds = [];
        $invalidUserHashes = [];
        $userAgentService = new UserAgentService();

        $entries = ConnectWarning::query()
            ->where('created_at', '>=', $date->clone()->startOfDay())
            ->where('created_at', '<=', $date->clone()->endOfDay())
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

                if ($smell === 'bad_validation' && $entry->related_id) {
                    $validationHash = strtolower($entry->validation_hash);

                    $existingId = (int) ($invalidUserHashes[$entry->username][$validationHash] ?? 0);
                    if ($existingId === 0) {
                        $invalidUserHashes[$entry->username][$validationHash] = $entry->related_id;
                    } elseif ($existingId !== $entry->related_id) {
                        $sniff['smells'][] = 'repeated_validation';
                        $sniff['repeatedHash' . ucfirst($entry->related_type) . 'Id'] = $existingId;
                    }
                }
            }

            if ($entry->offset !== null) {
                $sniff['offset'] = $entry->offset;
            }

            if ($entry->related_type === 'leaderboard') {
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
            ->withTrashed()
            ->select('id', 'username', 'display_name', 'Permissions', 'deleted_at', 'unranked_at')
            ->get()
            ->toArray();
        foreach ($users as $user) {
            $userInfos[strtolower($user['username'])] = $user;
            $userInfos[strtolower($user['display_name'])] = $user;
        }

        $leaderboards = Leaderboard::query()
            ->whereIn('id', $leaderboardIds)
            ->select('id', 'title')
            ->get()
            ->keyBy('id')
            ->toArray();

        foreach ($sniffs as &$sniff) {
            $lowerUsername = strtolower($sniff['user']);
            if (array_key_exists($lowerUsername, $userInfos)) {
                $sniff['userinfo'] = $userInfos[$lowerUsername];
                $sniff['link'] = route('user.show', $sniff['userinfo']['display_name']);
            } elseif (empty($lowerUsername)) {
                $sniff['smells'][] = 'no_user';
                $sniff['link'] = '';
            } else {
                $sniff['smells'][] = 'unknown_user';
                $sniff['link'] = route('user.show', $sniff['user']);
            }

            if (array_key_exists('leaderboardId', $sniff) && array_key_exists($sniff['leaderboardId'], $leaderboards)) {
                $sniff['leaderboard'] = $leaderboards[$sniff['leaderboardId']];
            }

        }

        return array_reverse($sniffs); // newest first
    }
}
