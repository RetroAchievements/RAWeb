<?php

use App\Enums\ClientSupportLevel;
use App\Enums\Permissions;
use App\Models\ConnectWarning;
use App\Models\Leaderboard;
use App\Models\User;
use App\Platform\Services\UserAgentService;
use Carbon\Carbon;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Moderator)) {
    abort(401);
}

// functions

function RenderFilterButton(string $text, string $filter, int $count): void
{
    if ($count > 0) {
        echo "<button class='btn' type='button' data-filter='$filter'>";
        echo "$text ($count)";
        echo '</button>';
    }
}

function key_to_class(?string $key): string
{
    if ($key === null) {
        return '_null_';
    }

    return str_replace([' ', '.'], '-', str_replace('%', '_pct_', $key));
}

// render

$clients = [
    'no_user_agent',
    'browser',
    'unknown_client',
    'blocked_client',
];

$files = [];
for ($i = 0; $i < 90; $i++) {
    $date = Carbon::now()->subDays($i);
    $files[] = "sentry-" . $date->format('Y-m-d');
}

$method_counts = [];
$smell_counts = [];
$user_counts = [];

$sniffs = [];
$selected = requestInputSanitized('file');
if ($selected) {
    $usernames = [];
    $leaderboardIds = [];
    $invalidUserHashes = [];
    $userAgentService = new UserAgentService();

    $date = Carbon::parse(substr($selected, 7));
    $entries = ConnectWarning::query()
        ->where('created_at', '>=', $date->clone()->startOfDay())
        ->where('created_at', '<=', $date->clone()->endOfDay())
        ->with('playerSession', 'playerSession.gameHash')
        ->orderBy('created_at')
        ->get();
    foreach ($entries as $entry) {
        $method_counts[$entry->method] = ($method_counts[$entry->method] ?? 0) + 1;
        $user_counts[$entry->username] = ($user_counts[$entry->username] ?? 0) + 1;
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
        if (empty($userAgent)) {
            $sniff['smells'][] = 'no_user_agent';
        } elseif (str_contains($userAgent, 'Mozilla')) {
            $sniff['smells'][] = 'browser';
        } elseif (in_array('invalid_client', $sniff['smells']) || in_array('blocked_client', $sniff['smells'])) {
            $data = $userAgentService->decode($userAgent);
            $client = $data['client'];
            $sniff['smells'][] = $client;
            if (!in_array($client, $clients)) {
                $clients[] = $client;
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
        } else {
            $sniff['link'] = route('user.show', $sniff['user']);
            $sniff['smells'][] = empty($lowerUsername) ? 'no_user' : 'unknown_user';
        }

        if (array_key_exists('leaderboardId', $sniff) && array_key_exists($sniff['leaderboardId'], $leaderboards)) {
            $sniff['leaderboard'] = $leaderboards[$sniff['leaderboardId']];
        }

        foreach ($sniff['smells'] as $smell) {
            $smell_counts[$smell] = ($smell_counts[$smell] ?? 0) + 1;
        }
    }

    $sniffs = array_reverse($sniffs); // newest first
}

ksort($user_counts);
ksort($method_counts);
ksort($smell_counts);
sort($clients);

$colors = [
    'bad_validation' => [ // validation hash did not match expected values
        'background' => 'orange',
        'border' => 'orangered',
    ],
    'repeated_validation' => [ // same validation hash use by user for a different achievement/leaderboard
        'background' => 'indianred',
        'border' => 'red',
    ],
    'softcore_validation' => [ // validation hash for hardcore unlocks matches softcore validation hash
        'background' => 'indianred',
        'border' => 'red',
    ],
    'no_validation' => [ // no validation hash was provided
        'background' => 'orange',
        'border' => 'orangered',
    ],
    'unknown_client' => [ // user agent not recognized
        'background' => 'gray',
        'border' => 'darkgray',
    ],
    'blocked_client' => [ // user agent for client that's permanently blocked
        'background' => 'gray',
        'border' => 'darkgray',
    ],
    'wrong_client' => [ // user agent provided for an emulator that doesn't support the system associated with the achievement/leaderboard
        'background' => 'indianred',
        'border' => 'red',
    ],
];

foreach ($clients as $client) {
    if (!array_key_exists($client, $colors)) {
        $colors[$client] = [
            'background' => 'darkslategray',
            'border' => 'green',
        ];
    }
}
?>
<x-app-layout pageTitle="Sentry">
    <x-slot name="sidebar">
        <h1>Sentry Logs</h1>
        <?php foreach ($files as $filename): ?>
            <div style="margin-bottom: 4px">
                <a href="?file=<?= $filename ?>">
                    <?= $filename ?>
                </a>
                <span style="color:white">
                    <?= $selected === $filename ? ' &laquo;' : '' ?>
                </span>
            </div>
        <?php endforeach ?>
    </x-slot>
    <?php if ($selected): ?>
        <div>
            <h2>
                <?= $selected ?>
            </h2>
            <div style="margin-bottom:10px">
                <table>
                <tr><td>Method</td><td>
                <?php RenderFilterButton('awardachievement', 'method-awardachievement', $method_counts['awardachievement'] ?? 0); ?>
                <?php RenderFilterButton('submitlbentry', 'method-submitlbentry', $method_counts['submitlbentry'] ?? 0); ?>
                </td></tr>
                <tr><td>Client</td><td>
                <?php
                    foreach ($clients as $client) {
                        RenderFilterButton($client, 'smell-' . key_to_class($client), $smell_counts[$client] ?? 0);
                    }
                ?>
                </td></tr>
                <tr><td>Validation</td><td>
                <?php
                    foreach ($smell_counts as $smell => $count) {
                        if (!in_array($smell, $clients)) {
                            RenderFilterButton($smell, 'smell-' . key_to_class($smell), $count);
                        }
                    }
                ?>
                </td></tr>
                <tr><td>User</td><td>
                <?php
                foreach ($user_counts as $user => $count) {
                    RenderFilterButton($user, 'user-' . key_to_class($user), $count);
                }
                RenderFilterButton('unknown_user', 'smell-unknown_user', $smell_counts['unknown_user'] ?? 0);
                RenderFilterButton('no_user', 'smell-no_user', $smell_counts['no_user'] ?? 0);
                ?>
                </td></tr>
                <tr><td/><td>
                <button class='btn' type="button" data-filter="">
                    Reset
                </button>
                </td></tr>
                </table>
            </div>
            <style>
                .hide {
                    display: none;
                }

                .btn.active {
                    background: green;
                    color: white;
                }
            </style>
            <script>
                let filterFlags = {};

                document.querySelectorAll('[data-filter]').forEach(function (el) {
                    el.addEventListener('click', function (event) {
                        const selector = event.currentTarget.getAttribute('data-filter');
                        event.currentTarget.classList.remove('active');
                        if (toggleFilter(selector)) {
                            event.currentTarget.classList.add('active');
                        }
                    })
                });

                const toggleFilter = function (selector) {
                    if (!selector) {
                        filterFlags = {};
                        applyFilters();
                        return;
                    }
                    const active = toggle(filterFlags, '.' + selector);
                    applyFilters();
                    return active;
                }

                function toggle(S, x) {
                    S[x] = 1 - (S[x] | 0);
                    return S[x];
                }

                const applyFilters = function () {
                    const activeFilters = Object.entries(filterFlags)
                        .filter(filterFlag => filterFlag[1] === 1)
                        .map(filterFlag => filterFlag[0]);
                    if (!activeFilters.length) {
                        document.querySelectorAll('.smell').forEach(el => el.classList.remove('hide'));
                        document.querySelectorAll('button').forEach(button => button.classList.remove('active'));
                        return;
                    }
                    document.querySelectorAll('.smell').forEach(el => el.classList.add('hide'));
                    document.querySelectorAll(activeFilters.join('')).forEach(el => el.classList.remove('hide'));
                }
            </script>
            <?php foreach ($sniffs as &$sniff): ?>
                <?php
                $classes = [
                    'smell',
                    'user-' . key_to_class($sniff['user']),
                    'method-' . $sniff['method'],
                    ...array_map(fn ($class) => 'smell-' . key_to_class($class), $sniff['smells'] ?? []),
                ];
                if (empty($sniff['userAgent'])) {
                    $classes[] = 'smell-no-user-agent';
                }
                ?>
                <details class="<?= implode(' ', $classes) ?>">
                    <summary class="cursor-pointer mb-2">
                        <code><?= $sniff['date'] ?></code>
                        <code style="padding:1px 5px;border:1px solid steelblue;background:royalblue;color:white"><?= $sniff['method'] ?></code>
                        <?php foreach ($sniff['smells'] ?? [] as $smell): ?>
                            <code style="padding:1px 5px;border:1px solid <?= $colors[$smell]['border'] ?? 'gray' ?>;background:<?= $colors[$smell]['background'] ?? 'gray' ?>;color:white"><?= $smell ?></code>
                        <?php endforeach ?>
                        <?php
                            if ($sniff['user']) {
                                echo "&middot; <a href='" . $sniff['link'] . "'>" . $sniff['user'] . '</a>';
                                if (!$sniff['userinfo']) {
                                    echo ' (non-existant)';
                                } elseif ($sniff['userinfo']['deleted_at'] ?? false) {
                                    echo ' (deleted)';
                                } elseif ($sniff['userinfo']['Permissions'] == Permissions::Banned) {
                                    echo ' (banned)';
                                } elseif ($sniff['userinfo']['unranked_at'] ?? false) {
                                    echo ' (untracked)';
                                }
                            }
                        ?>
                        <?php if ($sniff['achievement'] ?? null): ?>
                            &middot; <a href="/achievement/<?= $sniff['achievement']['id'] ?>"><?= $sniff['achievement']['title'] ?></a>
                            <?php if ($sniff['hardcore']): ?>
                                (hardcore)
                            <?php endif ?>
                        <?php endif ?>
                        <?php if ($sniff['leaderboard'] ?? null): ?>
                            &middot; <a href="/leaderboardinfo.php?i=<?= $sniff['leaderboard']['id'] ?>"><?= $sniff['leaderboard']['title'] ?></a>
                            &middot; <code><?= $sniff['score'] ?></code>
                        <?php endif ?>
                    </summary>
                    <?php
                    unset($sniff['achievement']);
                    unset($sniff['game']);
                    unset($sniff['leaderboard']);
                    unset($sniff['link']);
                    unset($sniff['userinfo']);
                    ?>
                    <pre><?= json_encode($sniff, JSON_PRETTY_PRINT) ?></pre>
                </details>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</x-app-layout>
