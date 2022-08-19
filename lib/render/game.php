<?php

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

    $gameIcon = $gameIcon != null ? $gameIcon : "/Images/PlayingIcon32.png";

    $tooltip = "<div id='objtooltip' style='display:flex;max-width:400px'>";
    $tooltip .= "<img style='margin-right:5px' src='" . asset($gameIcon) . "' width='$tooltipIconSize' height='$tooltipIconSize' />";
    $tooltip .= "<div>";
    $tooltip .= "<b>$gameName</b><br>";
    $tooltip .= $consoleStr;
    $tooltip .= "</div>";
    $tooltip .= "</div>";
    $tooltip = tipEscape($tooltip);

    $displayable = "";

    if (!$justText) {
        $displayable = "<img loading='lazy' alt='' title=\"$gameNameEscaped\" src='" . asset($gameIcon) . "' width='$imgSizeOverride' height='$imgSizeOverride' class='badgeimg' />";
    }

    if (!$justImage) {
        $displayable .= "$gameName $consoleStr";
    }

    return "<div class='bb_inline' onmouseover=\"Tip('$tooltip')\" onmouseout=\"UnTip()\" >" .
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

function RenderBoxArt($imagePath): void
{
    echo "<div class='component gamescreenshots'>";
    echo "<h3>Box Art</h3>";
    echo "<table><tbody>";
    echo "<tr><td>";
    echo "<img src='$imagePath' style='max-width:100%;' alt='Boxart'/>";
    echo "</td></tr>";
    echo "</tbody></table>";
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
            echo "<span style='white-space: nowrap'>$points points</span><span class='TrueRatio'> ($totalTP)</span>";
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
        echo "<td style='white-space: nowrap'>$label:</td>";
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
        echo "<a class='info-button' href='/viewtopic.php?t=$forumTopicID'><span>💬</span>Official forum topic</a>";
    } else {
        if ($permissions >= Permissions::Developer) {
            echo "<a class='info-button' href='/request/game/generate-forum-topic.php?g=$gameID' onclick='return confirm(\"Are you sure you want to create the official forum topic for this game?\")'><span>💬</span>Create the official forum topic for $gameTitle</a>";
        }
    }
}

function RenderRecentGamePlayers($recentPlayerData): void
{
    echo "<div class='component'>Recent Players:";
    echo "<table class='smalltable'><tbody>";
    echo "<tr><th>User</th><th>When</th><th>Activity</th>";

    foreach ($recentPlayerData as $recentPlayer) {
        echo "<tr>";

        $userName = $recentPlayer['User'];
        $date = $recentPlayer['Date'];
        $activity = $recentPlayer['Activity'];

        sanitize_outputs(
            $userName,
            $activity
        );

        echo "<td nowrap>";
        echo GetUserAndTooltipDiv($userName, true);
        echo GetUserAndTooltipDiv($userName, false);
        echo "</td>";

        echo "<td>$date</td>";
        echo "<td>$activity</td>";
        echo "</tr>";
    }

    echo "</tbody></table>";
    echo "</div>";
}
