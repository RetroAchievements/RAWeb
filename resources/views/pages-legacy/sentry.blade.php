<?php

use App\Connect\Actions\BuildConnectSniffsAction;
use App\Enums\Permissions;
use Carbon\Carbon;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Moderator)) {
    abort(401);
}

// functions

function RenderFilterButton(string $text, string $filter, int $count): void
{
    if ($count > 0) {
        echo "<button class='btn' type='button' data-filter='$filter'>";
        echo e($text) . " ($count)";
        echo '</button>';
    }
}

function key_to_class(?string $key): string
{
    if ($key === null) {
        return '_null_';
    }

    return preg_replace('/[^A-Za-z0-9_-]/', '-', str_replace('%', '_pct_', $key)) ?? '';
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
    $date = Carbon::parse(substr($selected, 7));
    $sniffs = (new BuildConnectSniffsAction())->execute($date, $clients);
} else {
    $selected = requestInputSanitized('user');
    if ($selected) {
        $sniffs = (new BuildConnectSniffsAction())->execute(null, $clients, $selected);
    }
}

foreach ($sniffs as $sniff) {
    $user_counts[$sniff['user']] = ($user_counts[$sniff['user']] ?? 0) + 1;
    $method_counts[$sniff['method']] = ($method_counts[$sniff['method']] ?? 0) + 1;

    foreach ($sniff['smells'] as $smell) {
        $smell_counts[$smell] = ($smell_counts[$smell] ?? 0) + 1;
    }
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
    'softcore_validation' => [ // validation hash for hardcore unlocks matches casual validation hash
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

// any unrecognized clients will be shown in green
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
                <?php
                    foreach ($method_counts as $method => $count) {
                        RenderFilterButton($method, 'method-' . key_to_class($method), $count);
                    }
                ?>
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

                .sniff-tag {
                    padding: 1px 5px;
                    border: 1px solid gray;
                    background: gray;
                    color: white;
                }

                .sniff-method {
                    border-color: steelblue;
                    background: royalblue;
                }

                <?php foreach ($colors as $smell => $color): ?>
                .tag-<?= key_to_class($smell) ?> {
                    border-color: <?= $color['border'] ?>;
                    background: <?= $color['background'] ?>;
                }
                <?php endforeach ?>
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

                // rows ship compact JSON to keep the page small. pretty-print it when a row is first opened.
                document.addEventListener('toggle', function (event) {
                    const details = event.target;
                    if (!(details instanceof HTMLDetailsElement) || !details.open) {
                        return;
                    }
                    const pre = details.querySelector('pre');
                    if (!pre || pre.dataset.pretty) {
                        return;
                    }
                    pre.dataset.pretty = '1';
                    pre.textContent = JSON.stringify(JSON.parse(pre.textContent), null, 4);
                }, true);
            </script>
            <?php
            // echoing shaves potentially dozens of MB off the page document size
            foreach ($sniffs as $sniff) {
                $classes = [
                    'smell',
                    'user-' . key_to_class($sniff['user']),
                    'method-' . key_to_class($sniff['method']),
                    ...array_map(fn ($class) => 'smell-' . key_to_class($class), $sniff['smells']),
                ];
                if (empty($sniff['userAgent'])) {
                    $classes[] = 'smell-no-user-agent';
                }

                echo '<details class="', implode(' ', $classes), '"><summary class="cursor-pointer mb-2">';
                echo '<code>', $sniff['date'], '</code> <code class="sniff-tag sniff-method">', e($sniff['method']), '</code>';
                foreach ($sniff['smells'] as $smell) {
                    echo ' <code class="sniff-tag tag-', key_to_class($smell), '">', e($smell), '</code>';
                }

                if ($sniff['user']) {
                    echo ' &middot; <a href="', e($sniff['link']), '">', e($sniff['user']), '</a>';
                    if (!array_key_exists('userinfo', $sniff)) {
                        echo ' (non-existant)';
                    } elseif ($sniff['userinfo']->deleted_at ?? false) {
                        echo ' (deleted)';
                    } elseif ($sniff['userinfo']->Permissions == Permissions::Banned) {
                        echo ' (banned)';
                    } elseif ($sniff['userinfo']->unranked_at ?? false) {
                        echo ' (untracked)';
                    }
                }

                if ($sniff['achievement'] ?? null) {
                    echo ' &middot; <a href="/achievement/', $sniff['achievement']->id, '">', e($sniff['achievement']->title), '</a>';
                    if ($sniff['hardcore']) {
                        echo ' (hardcore)';
                    }
                }

                if ($sniff['leaderboard'] ?? null) {
                    echo ' &middot; <a href="/leaderboardinfo.php?i=', $sniff['leaderboard']->id, '">', e($sniff['leaderboard']->title), '</a>';
                    echo ' &middot; <code>', e($sniff['score']), '</code>';
                }

                // remove the additional data we added for the UI before dumping the record
                unset($sniff['achievement'], $sniff['leaderboard'], $sniff['link'], $sniff['userinfo']);

                echo '</summary><pre>', htmlspecialchars(json_encode($sniff, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE), ENT_NOQUOTES), "</pre></details>\n";
            }
            ?>
        </div>
    <?php endif ?>
</x-app-layout>
