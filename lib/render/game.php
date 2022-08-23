<?php

use RA\LinkStyle;
use RA\Permissions;

function GetGameAndTooltipDiv(
    $gameID,
    $gameName,
    $gameIcon,
    $consoleName,
    $justImage = false,
    $imgSizeOverride = 32,
    $justText = false
): string {
    $tooltipIconSize = 64;

    $gameNameEscaped = attributeEscape($gameName);
    sanitize_outputs(
        $gameName,
        $consoleName
    );

    $consoleStr = '';
    if ($consoleName !== null && mb_strlen($consoleName) > 2) {
        $consoleStr = "($consoleName)";
    }

    $gameIcon = $gameIcon != null ? $gameIcon : "assets/images/activity/playing.webp";

    $tooltip = "<div id='objtooltip' class='flex items-start' style='max-width: 400px'>";
    $tooltip .= "<img style='margin-right:5px' src='" . media_asset($gameIcon) . "' width='$tooltipIconSize' height='$tooltipIconSize' />";
    $tooltip .= "<div>";
    $tooltip .= "<b>$gameName</b><br>";
    $tooltip .= $consoleStr;
    $tooltip .= "</div>";
    $tooltip .= "</div>";
    $tooltip = tipEscape($tooltip);

    $displayable = "";

    if (!$justText) {
        $displayable = "<img loading='lazy' alt=\"$gameNameEscaped\" src='" . media_asset($gameIcon) . "' width='$imgSizeOverride' height='$imgSizeOverride' class='badgeimg'>";
    }

    if (!$justImage) {
        $displayable .= " $gameName $consoleStr";
    }

    return "<div class='inline' onmouseover=\"Tip('$tooltip')\" onmouseout=\"UnTip()\" >" .
        "<a href='/game/$gameID'>" .
        "$displayable" .
        "</a>" .
        "</div>";
}

function RenderMostPopularTitles($daysRange = 7, $offset = 0, $count = 10): void
{
    $historyData = GetMostPopularTitles($daysRange, $offset, $count);

    echo "<div id='populargames' class='component' >";

    echo "<h3>Most Popular This Week</h3>";
    echo "<div id='populargamescomponent'>";

    echo "<table><tbody>";
    echo "<tr><th colspan='2'>Game</th><th>Times Played</th></tr>";

    $numItems = count($historyData);
    for ($i = 0; $i < $numItems; $i++) {
        $nextData = $historyData[$i];
        $nextID = $nextData['ID'];
        $nextTitle = $nextData['Title'];
        $nextIcon = $nextData['ImageIcon'];
        $nextConsole = $nextData['ConsoleName'];

        echo "<tr>";

        echo "<td class='gameimage'>";
        echo GetGameAndTooltipDiv($nextID, $nextTitle, $nextIcon, $nextConsole, true, 32, false);
        echo "</td>";

        echo "<td class='gametext'>";
        echo GetGameAndTooltipDiv($nextID, $nextTitle, $nextIcon, $nextConsole, false, 32, true);
        echo "</td>";

        echo "<td class='sumtotal'>";
        echo $nextData['PlayedCount'];
        echo "</td>";

        echo "</tr>";
    }

    echo "</tbody></table>";
    echo "</div>";

    echo "</div>";
}

function RenderGameAlts($gameAlts, $headerText = null): void
{
    echo "<div class='component gamealts'>";
    if ($headerText) {
        echo "<h3>$headerText</h3>";
    }
    echo "<table><tbody>";
    foreach ($gameAlts as $nextGame) {
        echo "<tr>";
        $gameID = $nextGame['gameIDAlt'];
        $gameTitle = $nextGame['Title'];
        $gameIcon = $nextGame['ImageIcon'];
        $consoleName = $nextGame['ConsoleName'];
        $points = $nextGame['Points'];
        $totalTP = $nextGame['TotalTruePoints'];
        settype($points, 'integer');
        settype($totalTP, 'integer');

        sanitize_outputs(
            $gameTitle,
            $consoleName,
        );

        $isFullyFeaturedGame = $consoleName != 'Hubs';
        if (!$isFullyFeaturedGame) {
            $consoleName = null;
        }

        echo "<td>";
        echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, true);
        echo "</td>";

        echo "<td style='width: 100%' " . ($isFullyFeaturedGame ? '' : 'colspan="2"') . ">";
        echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, false, 32, true);
        echo "</td>";

        if ($isFullyFeaturedGame) {
            echo "<td>";
            echo "<span class='whitespace-nowrap'>$points points</span><span class='TrueRatio'> ($totalTP)</span>";
            echo "</td>";
        }

        echo "</tr>";
    }
    echo "</tbody></table>";
    echo "</div>";
}

function RenderMetadataTableRow($label, $gameDataValue, $gameHubs = null, $altLabels = []): void
{
    $gameDataValues = !empty($gameDataValue) ? array_map('trim', explode(',', $gameDataValue)) : [];
    $unmergedKeys = array_keys($gameDataValues);

    if ($gameHubs) {
        $mergeMetadata = function ($hubCategory) use (&$gameHubs, &$gameDataValues, &$unmergedKeys) {
            $hubPrefix = "[$hubCategory - ";
            foreach ($gameHubs as $hub) {
                $title = $hub['Title'];
                if (str_starts_with($title, $hubPrefix)) {
                    if (str_starts_with($hubCategory, "Hack")) {
                        // for Hacks, we do want to display the hub category, but it
                        // should be normalized to "Hack - XXX".

                        // the hub name will always be "[Hacks - XXX]"
                        // strip the brackets and attempt to match the hub name explicitly
                        $value = substr($title, 1, -1);
                        $key = array_search($value, $gameDataValues);

                        // normalize to "Hack - XXX";
                        $value = str_replace("Hacks - ", "Hack - ", $value);

                        if ($key === false) {
                            // non-normalized value did not match, try normalized value
                            $key = array_search($value, $gameDataValues);
                        }
                    } else {
                        // strip the category and brackets and look for an exact match
                        $value = substr($title, strlen($hubPrefix), -1);
                        $key = array_search($value, $gameDataValues);
                    }

                    $link = "<a href=/game/" . $hub['gameIDAlt'] . ">$value</a>";

                    if ($key !== false) {
                        $gameDataValues[$key] = $link;
                        unset($unmergedKeys[$key]);
                    } else {
                        $gameDataValues[] = $link;
                    }
                }
            }
        };

        $mergeMetadata($label);

        foreach ($altLabels as $altLabel) {
            $mergeMetadata($altLabel);
        }
    }

    if (!empty($gameDataValues)) {
        foreach ($unmergedKeys as $key) {
            sanitize_outputs($gameDataValues[$key]);
        }

        echo "<tr>";
        echo "<td>$label</td>";
        echo "<td><b>" . implode(', ', $gameDataValues) . "</b></td>";
        echo "</tr>";
    }
}

function RenderLinkToGameForum($gameTitle, $gameID, $forumTopicID, $permissions = 0): void
{
    sanitize_outputs(
        $gameTitle,
    );

    if (isset($forumTopicID) && $forumTopicID != 0 && getTopicDetails($forumTopicID, $topicData)) {
        echo "<a class='btn py-2 mb-2 block' href='/viewtopic.php?t=$forumTopicID'><span class='icon icon-md ml-1 mr-3'>💬</span>Official Forum Topic</a>";
    } else {
        if ($permissions >= Permissions::Developer) {
            echo "<form action='/request/game/generate-forum-topic.php' method='post' onsubmit='return confirm(\"Are you sure you want to create the official forum topic for this game?\")'>";
            echo csrf_field();
            echo "<input type='hidden' name='game' value='$gameID'>";
            echo "<button class='btn btn-link py-2 mb-1 w-full'><span class='icon icon-md ml-1 mr-3'>💬</span>Create Forum Topic</button>";
            echo "</form>";
        }
    }
}

function RenderRecentGamePlayers($recentPlayerData): void
{
    echo "<div class='component'>Recent Players:";
    echo "<table><tbody>";
    echo "<tr><th>User</th><th>When</th><th class='w-full'>Activity</th>";
    foreach ($recentPlayerData as $recentPlayer) {
        echo "<tr>";
        $userName = $recentPlayer['User'];
        $date = $recentPlayer['Date'];
        $activity = $recentPlayer['Activity'];
        sanitize_outputs(
            $userName,
            $activity
        );
        echo "<td>";
        RenderUserLink($userName, LinkStyle::MediumImageWithText);
        echo "</td>";
        echo "<td class='whitespace-nowrap'>$date</td>";
        echo "<td>$activity</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
    echo "</div>";
}

function RenderGameProgress(int $numAchievements, int $numEarnedCasual, int $numEarnedHardcore)
{
    $pctComplete = 0;
    $pctHardcore = 0;
    $pctHardcoreProportion = 0;
    $title = '';

    if ($numAchievements) {
        $pctAwardedCasual = ($numEarnedCasual + $numEarnedHardcore) / $numAchievements;
        $pctAwardedHardcore = $numEarnedHardcore / $numAchievements;
        $pctAwardedHardcoreProportion = 0;
        if ($numEarnedHardcore > 0) {
            $pctAwardedHardcoreProportion = $numEarnedHardcore / ($numEarnedHardcore + $numEarnedCasual);
        }

        $pctComplete = sprintf("%01.0f", floor($pctAwardedCasual * 100.0));
        $pctHardcore = sprintf("%01.0f", floor($pctAwardedHardcore * 100.0));
        $pctHardcoreProportion = sprintf("%01.0f", $pctAwardedHardcoreProportion * 100.0);

        if ($numEarnedCasual && $numEarnedHardcore) {
            $title = "$pctHardcore% hardcore";
        }
    }

    echo "<div class='flex flex-col items-start md:items-center my-2'>";
    echo "<div class='progressbar'>";
    echo "<div class='completion' style='width:$pctComplete%' title='$title'>";
    echo "<div class='completion-hardcore' style='width:$pctHardcoreProportion%'></div>";
    echo "</div>";
    echo "</div>";
    echo "<div class='progressbar-label md:text-center'>";
    if ($pctHardcore >= 100.0) {
        echo "Mastered";
    } else {
        echo "$pctComplete% complete";
    }
    echo "</div>";
    echo "</div>";
}
