<?php

use App\Enums\Permissions;
use App\Models\ForumTopic;
use App\Models\System;
use Illuminate\Support\Facades\Blade;

/**
 * @deprecated use <x-game.avatar />
 */
function gameAvatar(
    int|string|array $game,
    ?bool $label = null,
    bool|string|null $icon = null,
    int $iconSize = 32,
    string $iconClass = 'badgeimg',
    bool $tooltip = true,
    ?string $context = null,
    ?string $title = null,
): string {
    $id = $game;

    if (is_array($game)) {
        $id = $game['GameID'] ?? $game['ID'];

        if ($label !== false) {
            if ($title === null) {
                $title = $game['GameTitle'] ?? $game['Title'] ?? null;

                $consoleName = $game['Console'] ?? $game['ConsoleName'] ?? null;
                if ($consoleName) {
                    $title .= " ($consoleName)";
                }
            }

            sanitize_outputs($title);   // sanitize before rendering HTML
            $label = Blade::render('<x-game-title :rawTitle="$rawTitle" />', ['rawTitle' => $title]);
            $label = preg_replace('/\s+/', ' ', $label);
        }

        if ($icon === null) {
            $icon = media_asset($game['GameIcon'] ?? $game['ImageIcon']);
        }
    }

    return avatar(
        resource: 'game',
        id: $id,
        label: $label !== false && ($label || !$icon) ? $label : null,
        link: route('game.show', $id),
        tooltip: $tooltip,
        iconUrl: $icon !== false && ($icon || !$label) ? $icon : null,
        iconSize: $iconSize,
        iconClass: $iconClass,
        context: $context,
        sanitize: $title === null,
        altText: $title ?? (is_string($label) ? $label : null),
    );
}

/**
 * Render game breadcrumb prefix, with optional link on last crumb
 *
 * Format: `All Games » (console) » (game title)`.
 * If given data is for a subset, then `» Subset - (name)` is also added.
 *
 * @deprecated use <x-game.breadcrumbs />
 */
function renderGameBreadcrumb(array|int $data, bool $addLinkToLastCrumb = true): string
{
    if (is_int($data)) {
        $data = getGameData($data);
    }
    // TODO refactor to Game
    $consoleID = $data['ConsoleID'];
    $consoleName = $data['ConsoleName'];

    // Return next crumb (i.e `» text`), either as a link or not
    $nextCrumb = fn ($text, $href = ''): string => " &raquo; " . ($href ? "<a href='$href'>$text</a>" : "<span class='font-bold'>$text</span>");

    // Retrieve separate IDs and titles for main game and subset (if any)
    $getSplitData = function ($data) use ($consoleID): array {
        $gameID = $data['GameID'] ?? $data['ID'];
        $gameTitle = $data['GameTitle'] ?? $data['Title'];
        // Match and possibly split main title and subset
        $mainID = $gameID;
        $mainTitle = $gameTitle;
        $matches = [];
        if (preg_match('/(.+)(\[Subset - .+\])/', $gameTitle, $matches)) {
            $mainTitle = trim($matches[1]);
            $subset = $matches[2];
            $mainID = getGameIDFromTitle($mainTitle, $consoleID);
            $subsetID = $gameID;
            $renderedSubset = Blade::render('<x-game-title :rawTitle="$rawTitle" />', ['rawTitle' => $subset]);
        }

        $renderedMain = Blade::render('
            <x-game-title
                :rawTitle="$rawTitle"
                :showTags="$showTags"
            />', [
                'rawTitle' => $mainTitle,
                'showTags' => false,
            ]
        );

        if ($renderedMain !== $mainTitle) {
            // In the rare case of a same-console derived game sharing identical
            // title with a base one, include category to solve ambiguity
            $baseTitle = trim(substr($mainTitle, strrpos($mainTitle, '~') + 1));
            $baseID = getGameIDFromTitle($baseTitle, $consoleID);
            if ($baseID) {
                $renderedMain = Blade::render('<x-game-title :rawTitle="$rawTitle" />', ['rawTitle' => $mainTitle]);
            }
        }

        return [$mainID, $renderedMain, $subsetID ?? null, $renderedSubset ?? null];
    };

    $gameListHref = System::isGameSystem($consoleID)
        ? route('system.game.index', ['system' => $consoleID])
        : '/gameList.php?c=' . $consoleID;

    $html = "<a href='/gameList.php'>All Games</a>"
        . $nextCrumb($consoleName, $gameListHref);

    [$mainID, $renderedMain, $subsetID, $renderedSubset] = $getSplitData($data);
    $baseHref = (($addLinkToLastCrumb || $subsetID) && $mainID) ? "/game/$mainID" : '';
    $html .= $nextCrumb($renderedMain, $baseHref);
    if ($subsetID) {
        $html .= $nextCrumb($renderedSubset, $addLinkToLastCrumb ? "/game/$subsetID" : '');
    }

    return $html;
}

function renderGameCard(int|array $game, ?string $targetUsername): string
{
    $gameId = is_int($game) ? $game : ($game['GameID'] ?? $game['ID'] ?? null);

    if (empty($gameId)) {
        return __('legacy.error.error');
    }

    return Blade::render('<x-game-card :gameId="$gameId" :targetUsername="$targetUsername" />', [
        'gameId' => $gameId,
        'targetUsername' => $targetUsername,
    ]);
}

function RenderGameSort(
    bool $isFullyFeaturedGame,
    ?int $flag,
    int $officialFlag,
    int $gameID,
    ?int $sortBy,
    bool $canSortByType = false,
): void {
    echo "<div><span>";
    echo "Sort: ";

    $flagParam = ($flag != $officialFlag) ? "f=$flag" : '';

    $sortType = ($sortBy < 10) ? "^" : "<sup>v</sup>";
    // Used for options which sort in Descending order on first click
    $sortReverseType = ($sortBy >= 10) ? "^" : "<sup>v</sup>";

    $sort1 = ($sortBy == 1) ? 11 : 1;
    $sort2 = ($sortBy == 2) ? 12 : 2;
    $sort3 = ($sortBy == 3) ? 13 : 3;
    $sort4 = ($sortBy == 4) ? 14 : 4;
    $sort5 = ($sortBy == 5) ? 15 : 5;
    $sort6 = ($sortBy == 6) ? 16 : 6;

    $mark1 = ($sortBy % 10 == 1) ? "&nbsp;$sortType" : "";
    $mark2 = ($sortBy % 10 == 2) ? "&nbsp;$sortType" : "";
    $mark3 = ($sortBy % 10 == 3) ? "&nbsp;$sortType" : "";
    $mark4 = ($sortBy % 10 == 4) ? "&nbsp;$sortType" : "";
    $mark5 = ($sortBy % 10 == 5) ? "&nbsp;$sortType" : "";
    $mark6 = ($sortBy % 10 == 6) ? "&nbsp;$sortType" : "";

    $reverseMark1 = ($sortBy % 10 == 1) ? "&nbsp;$sortReverseType" : "";
    $reverseMark2 = ($sortBy % 10 == 2) ? "&nbsp;$sortReverseType" : "";
    $reverseMark3 = ($sortBy % 10 == 3) ? "&nbsp;$sortReverseType" : "";
    $reverseMark4 = ($sortBy % 10 == 4) ? "&nbsp;$sortReverseType" : "";
    $reverseMark5 = ($sortBy % 10 == 5) ? "&nbsp;$sortReverseType" : "";
    $reverseMark6 = ($sortBy % 10 == 6) ? "&nbsp;$sortReverseType" : "";

    if ($isFullyFeaturedGame) {
        echo "<a href='/game/$gameID?$flagParam&s=$sort1'>Normal$mark1</a> - ";
        echo "<a href='/game/$gameID?$flagParam&s=$sort2'>Won By$mark2</a> - ";
        // TODO sorting by "date won" isn't implemented yet.
        // if(isset($user)) {
        //    echo "<a href='/game/$gameID?$flagParam&s=$sort3'>Date Won$mark3</a> - ";
        // }
        echo "<a href='/game/$gameID?$flagParam&s=$sort4'>Points$mark4</a> - ";
        echo "<a href='/game/$gameID?$flagParam&s=$sort5'>Title$mark5</a>";
        if ($canSortByType) {
            echo " - ";
            echo "<a href='/game/$gameID?$flagParam&s=$sort6'>Type$mark6</a>";
        }
    } else {
        echo "<a href='/game/$gameID?$flagParam&s=$sort1'>Default$mark1</a> - ";
        echo "<a href='/game/$gameID?$flagParam&s=$sort2'>Retro Points$reverseMark2</a>";
    }

    echo "<sup>&nbsp;</sup></span></div>";
}

function RenderGameAlts(array $gameAlts, ?string $headerText = null): void
{
    echo "<div class='component gamealts'>";
    if ($headerText) {
        echo "<h2 class='text-h3'>$headerText</h2>";
    }
    echo "<table class='table-highlight'><tbody>";
    foreach ($gameAlts as $nextGame) {
        $consoleName = $nextGame['ConsoleName'];
        $points = $nextGame['Points'];
        $totalTP = $nextGame['TotalTruePoints'];
        $points = (int) $points;
        $totalTP = (int) $totalTP;

        $isFullyFeaturedGame = $consoleName != 'Hubs';
        if (!$isFullyFeaturedGame) {
            $consoleName = null;
        }

        echo Blade::render('
            <x-game.similar-game-table-row
                :gameId="$gameId"
                :gameTitle="$gameTitle"
                :gameImageIcon="$gameImageIcon"
                :consoleName="$consoleName"
                :totalPoints="$totalPoints"
                :totalRetroPoints="$totalRetroPoints"
                :isFullyFeaturedGame="$isFullyFeaturedGame"
            />
        ', [
            'gameId' => $nextGame['gameIDAlt'],
            'gameTitle' => $nextGame['Title'],
            'gameImageIcon' => $nextGame['ImageIcon'],
            'consoleName' => $consoleName,
            'totalPoints' => $points,
            'totalRetroPoints' => $totalTP,
            'isFullyFeaturedGame' => $isFullyFeaturedGame,
        ]);
    }

    echo "</tbody></table>";
    echo "</div>";
}

function RenderLinkToGameForum(string $gameTitle, int $gameID, ?int $forumTopicID, int $permissions = Permissions::Unregistered): void
{
    sanitize_outputs(
        $gameTitle,
    );

    if (!empty($forumTopicID) && ForumTopic::where('ID', $forumTopicID)->exists()) {
        echo "<a class='btn py-2 mb-2 block' href='/viewtopic.php?t=$forumTopicID'><span class='icon icon-md ml-1 mr-3'>💬</span>Official Forum Topic</a>";
    } else {
        if ($permissions >= Permissions::Developer) {
            echo "<form action='/request/game/generate-forum-topic.php' method='post' onsubmit='return confirm(\"Are you sure you want to create the official forum topic for this game?\")'>";
            echo csrf_field();
            echo "<input type='hidden' name='game' value='$gameID'>";
            echo "<button class='btn btn-link py-2 mb-2 w-full'><span class='icon icon-md ml-1 mr-3'>💬</span>Create Forum Topic</button>";
            echo "</form>";
        }
    }
}

function generateGameMetaDescription(
    string $gameTitle,
    string $consoleName,
    int $numAchievements = 0,
    int $gamePoints = 0,
    bool $isEventGame = false,
): string {
    if ($isEventGame) {
        return "$gameTitle: An event at RetroAchievements. Check out the page for more details on this unique challenge.";
    } elseif ($numAchievements === 0) {
        return "No achievements have been created yet for $gameTitle. Join RetroAchievements to request achievements for $gameTitle and earn achievements on many other classic games.";
    }

    $localizedPoints = localized_number($gamePoints);

    return "There are $numAchievements achievements worth $localizedPoints points. $gameTitle for $consoleName - explore and compete on this classic game at RetroAchievements.";
}

function generateEmptyBucketsWithBounds(int $numAchievements): array
{
    $DYNAMIC_BUCKETING_THRESHOLD = 44;
    $GENERATED_RANGED_BUCKETS_COUNT = 20;

    // Enable bucketing based on the number of achievements in the set.
    // This number was picked arbitrarily, but generally reflects when we start seeing
    // width constraints in the Achievements Distribution bar chart.
    $isDynamicBucketingEnabled = $numAchievements >= $DYNAMIC_BUCKETING_THRESHOLD;

    // If bucketing is enabled, we'll dynamically generate 19 buckets. The final 20th
    // bucket will contain all users who have completed/mastered the game.
    $bucketCount = $isDynamicBucketingEnabled ? $GENERATED_RANGED_BUCKETS_COUNT : $numAchievements;

    // Bucket size is determined based on the total number of achievements in the set.
    // If bucketing is enabled, we aim for roughly 20 buckets (hence dividing by $bucketCount).
    // If bucketing is not enabled, each achievement gets its own bucket (bucket size is 1).
    $bucketSize = $isDynamicBucketingEnabled ? ($numAchievements - 1) / $bucketCount : 1;

    $buckets = [];
    $currentUpperBound = 1;
    for ($i = 0; $i < $bucketCount; $i++) {
        if ($isDynamicBucketingEnabled) {
            $start = $i === 0 ? 1 : $currentUpperBound + 1;
            $end = intval(round($bucketSize * ($i + 1)));
            $buckets[$i] = ['start' => $start, 'end' => $end, 'hardcore' => 0, 'softcore' => 0];

            $currentUpperBound = $end;
        } else {
            $buckets[$i] = ['start' => $i + 1, 'end' => $i + 1, 'hardcore' => 0, 'softcore' => 0];
        }
    }

    return [$buckets, $isDynamicBucketingEnabled];
}

function findBucketIndex(array $buckets, int $achievementNumber): int
{
    $low = 0;
    $high = count($buckets) - 1;

    // Perform a binary search.
    while ($low <= $high) {
        $mid = intdiv($low + $high, 2);
        if ($achievementNumber >= $buckets[$mid]['start'] && $achievementNumber <= $buckets[$mid]['end']) {
            return $mid;
        }
        if ($achievementNumber < $buckets[$mid]['start']) {
            $high = $mid - 1;
        } else {
            $low = $mid + 1;
        }
    }

    // Error: This should not happen unless something is terribly wrong with the page.
    return -1;
}

function calculateBuckets(
    array &$buckets,
    bool $isDynamicBucketingEnabled,
    int $numAchievements,
    array $achDist,
    array $achDistHardcore
): array {
    $largestWonByCount = 0;

    // Iterate through the achievements and distribute them into the buckets.
    for ($i = 1; $i < $numAchievements; $i++) {
        // Determine the bucket index based on the current achievement number.
        $targetBucketIndex = $isDynamicBucketingEnabled ? findBucketIndex($buckets, $i) : $i - 1;

        // Distribute the achievements into the bucket by adding the number of hardcore
        // users who achieved it and the number of softcore users who achieved it to
        // the respective counts.
        $wonByUserCount = $achDist[$i];
        $buckets[$targetBucketIndex]['hardcore'] += $achDistHardcore[$i];
        $buckets[$targetBucketIndex]['softcore'] += $wonByUserCount - $achDistHardcore[$i];

        // We need to also keep tracked of `largestWonByCount`, which is later used for chart
        // configuration, such as determining the number of gridlines to show.
        $currentTotal = $buckets[$targetBucketIndex]['hardcore'] + $buckets[$targetBucketIndex]['softcore'];
        $largestWonByCount = max($currentTotal, $largestWonByCount);
    }

    return [$buckets, $largestWonByCount];
}

function handleAllAchievementsCase(int $numAchievements, array $achDist, array $achDistHardcore, array &$buckets): int
{
    if ($numAchievements <= 0) {
        return 0;
    }

    // Add a bucket for the users who have earned all achievements.
    $buckets[] = [
        'hardcore' => $achDistHardcore[$numAchievements],
        'softcore' => $achDist[$numAchievements] - $achDistHardcore[$numAchievements],
    ];

    // Calculate the total count of users who have earned all achievements.
    // This will later be used for chart configuration in determining the
    // number of gridlines to show on one of the axes.
    $allAchievementsCount = (
        $achDistHardcore[$numAchievements] + ($achDist[$numAchievements] - $achDistHardcore[$numAchievements])
    );

    return $allAchievementsCount;
}

function printBucketIteration(int $bucketIteration, int $numAchievements, array $bucket, string $label): void
{
    echo "[ {v:$bucketIteration, f:\"$label\"}, {$bucket['hardcore']}, {$bucket['softcore']} ]";
}

function generateBucketLabelsAndValues(int $numAchievements, array $buckets): array
{
    $bucketLabels = [];
    $hAxisValues = [];
    $bucketIteration = 0;
    $bucketCount = count($buckets);

    // Loop through each bucket to generate their labels and values.
    foreach ($buckets as $index => $bucket) {
        if ($bucketIteration++ > 0) {
            echo ", ";
        }

        // Is this the last bucket? If so, we only want it to include
        // players who have earned all the achievements, not a range.
        if ($index == $bucketCount - 1) {
            $label = "Earned $numAchievements achievements";
            printBucketIteration($bucketIteration, $numAchievements, $bucket, $label);

            $hAxisValues[] = $numAchievements;
        } else {
            // For other buckets, the label indicates the range of achievements that
            // the bucket represents.
            $start = $bucket['start'];
            $end = $bucket['end'];

            // Pluralize 'achievement' if the range contains more than one achievement.
            $plural = $end > 1 ? 's' : '';
            $label = "Earned $start achievement$plural";
            if ($start !== $end) {
                $label = "Earned $start-$end achievement$plural";
            }

            printBucketIteration($bucketIteration, $numAchievements, $bucket, $label);

            $hAxisValues[] = $start;
        }
    }

    return $hAxisValues;
}

function ListGames(
    array $gamesList,
    string $queryParams = '',
    int $sortBy = 0,
    bool $showTickets = false,
    bool $showConsoleName = false,
    bool $showTotals = false,
    bool $showClaims = false,
): void {
    echo "\n<div class='table-wrapper'><table class='table-highlight'><tbody>";

    $sort1 = ($sortBy <= 1) ? 11 : 1;
    $sort2 = ($sortBy == 2) ? 12 : 2;
    $sort3 = ($sortBy == 3) ? 13 : 3;
    $sort4 = ($sortBy == 4) ? 14 : 4;
    $sort5 = ($sortBy == 5) ? 15 : 5;
    $sort6 = ($sortBy == 6) ? 16 : 6;
    $sort7 = ($sortBy == 7) ? 17 : 7;

    echo "<tr class='do-not-highlight'>";
    echo "<th><a href='/gameList.php?s=$sort1$queryParams'>Title</a></th>";
    echo "<th class='text-right'><a href='/gameList.php?s=$sort2$queryParams'>Achievements</a></th>";
    echo "<th class='text-right'><a href='/gameList.php?s=$sort3$queryParams'>Points</a></th>";
    echo "<th class='text-right'><a href='/gameList.php?s=$sort7$queryParams'>Retro Ratio</a></th>";
    echo "<th style='white-space: nowrap' class='text-right'><a href='/gameList.php?s=$sort6$queryParams'>Last Updated</a></th>";
    echo "<th class='text-right'><a href='/gameList.php?s=$sort4$queryParams'>Leaderboards</a></th>";

    if ($showTickets) {
        echo "<th class='whitespace-nowrap text-right'><a href='/gameList.php?s=$sort5&$queryParams'>Open Tickets</a></th>";
    }

    if ($showClaims) {
        echo "<th class='whitespace-nowrap'>Claimed By</th>";
    }

    echo "</tr>";

    $gameCount = 0;
    $pointsTally = 0;
    $achievementsTally = 0;
    $truePointsTally = 0;
    $lbCount = 0;
    $ticketsCount = 0;

    foreach ($gamesList as $gameEntry) {
        $title = $gameEntry['Title'];
        $gameID = $gameEntry['ID'];
        $maxPoints = $gameEntry['MaxPointsAvailable'] ?? 0;
        $totalTrueRatio = $gameEntry['TotalTruePoints'];
        $retroRatio = $gameEntry['RetroRatio'];
        $totalAchievements = null;
        $numAchievements = $gameEntry['NumAchievements'];
        $numPoints = $maxPoints;
        $numTrueRatio = $totalTrueRatio;
        $numLBs = $gameEntry['NumLBs'];

        sanitize_outputs($title);

        echo "<tr>";

        if ($showConsoleName) {
            echo "<td class='pr-0 py-2 w-full xl:w-auto'>";
        } else {
            echo "<td class='pr-0 w-full xl:w-auto'>";
        }
        echo Blade::render('
            <x-game.multiline-avatar
                :gameId="$gameId"
                :gameTitle="$gameTitle"
                :gameImageIcon="$gameImageIcon"
                :consoleName="$consoleName"
            />
        ', [
            'gameId' => $gameEntry['ID'],
            'gameTitle' => $gameEntry['Title'],
            'gameImageIcon' => $gameEntry['GameIcon'],
            'consoleName' => $showConsoleName ? $gameEntry['ConsoleName'] : null,
        ]);
        echo "</td>";

        echo "<td class='text-right'>$numAchievements</td>";
        echo "<td class='whitespace-nowrap text-right'>" . localized_number($maxPoints);
        echo Blade::render("<x-points-weighted-container>(" . localized_number($numTrueRatio) . ")</x-points-weighted-container>");
        echo "</td>";
        echo "<td class='text-right'>$retroRatio</td>";

        if ($gameEntry['DateModified'] != null) {
            $lastUpdated = date("d M, Y", strtotime($gameEntry['DateModified']));
            echo "<td class='text-right'>$lastUpdated</td>";
        } else {
            echo "<td/>";
        }

        echo "<td class='text-right'>";
        if ($numLBs > 0) {
            echo "<a href=\"game/$gameID\">$numLBs</a>";
            $lbCount += $numLBs;
        }
        echo "</td>";

        if ($showTickets) {
            $openTickets = $gameEntry['OpenTickets'];
            echo "<td class='text-right'>";
            if ($openTickets > 0) {
                echo "<a href='ticketmanager.php?g=$gameID'>$openTickets</a>";
                $ticketsCount += $openTickets;
            }
            echo "</td>";
        }

        if ($showClaims) {
            echo "<td>";
            if (array_key_exists('ClaimedBy', $gameEntry)) {
                foreach ($gameEntry['ClaimedBy'] as $claimUser) {
                    echo userAvatar($claimUser);
                    echo "</br>";
                }
            }
            echo "</td>";
        }

        echo "</tr>";

        $gameCount++;
        $pointsTally += $numPoints;
        $achievementsTally += $numAchievements;
        $truePointsTally += $numTrueRatio;
    }

    if ($showTotals) {
        // Totals:
        echo "<tr class='do-not-highlight'>";
        echo "<td><b>Totals: " . localized_number($gameCount) . " " . trans_choice(__('resource.game.title'), $gameCount) . "</b></td>";
        echo "<td class='text-right'><b>" . localized_number($achievementsTally) . "</b></td>";
        echo "<td class='text-right'><b>" . localized_number($pointsTally) . "</b>";
        echo Blade::render("<x-points-weighted-container>(" . localized_number($truePointsTally) . ")</x-points-weighted-container>");
        echo "</td>";
        echo "<td></td>";
        echo "<td></td>";
        echo "<td class='text-right'><b>" . localized_number($lbCount) . "</b></td>";
        if ($showTickets) {
            echo "<td class='text-right'><b>" . localized_number($ticketsCount) . "</b></td>";
        }
        echo "</tr>";
    }

    echo "</tbody></table></div>";
}

function renderConsoleHeading(int $consoleID, string $consoleName, bool $isSmall = false): string
{
    $systemIconUrl = getSystemIconUrl($consoleID);
    $iconSize = $isSmall ? 24 : 32;
    $headingSizeClassName = $isSmall ? 'text-h3' : '';

    return <<<HTML
        <h2 class="flex gap-x-2 items-center $headingSizeClassName">
            <img src="$systemIconUrl" alt="Console icon" width="$iconSize" height="$iconSize">
            <span>$consoleName</span>
        </h2>
    HTML;
}
