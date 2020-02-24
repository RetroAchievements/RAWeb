<?php

use RA\Permissions;

/**
 * @param $gameID
 * @param $gameName
 * @param $gameIcon
 * @param $consoleName
 * @param bool $justImage
 * @param int $imgSizeOverride
 * @param bool $justText
 * @return string
 */
function GetGameAndTooltipDiv(
    $gameID,
    $gameName,
    $gameIcon,
    $consoleName,
    $justImage = false,
    $imgSizeOverride = 32,
    $justText = false
) {
    $tooltipIconSize = 64; //96;

    $consoleStr = '';
    if ($consoleName !== null && mb_strlen($consoleName) > 2) {
        $consoleStr = "($consoleName)";
    }

    $gameIcon = $gameIcon != null ? $gameIcon : "/Images/PlayingIcon32.png";

    $tooltip = "<div id='objtooltip'>" .
        "<img src='$gameIcon' width='$tooltipIconSize' height='$tooltipIconSize' />" .
        "<b>$gameName</b><br>" .
        "$consoleStr" .
        "</div>";

    // $tooltip = str_replace('<', '&lt;', $tooltip);
    // $tooltip = str_replace('>', '&gt;', $tooltip);
    //echo $tooltip;
    //$tooltip = str_replace( "'", "\\'", $tooltip );
    //echo $tooltip;

    $tooltip = str_replace("'", "\'", $tooltip);
    $tooltip = htmlentities($tooltip);

    $displayable = "";

    if ($justText == false) {
        $displayable = "<img alt='' title=\"$gameName\" src='" . getenv('ASSET_URL') . "$gameIcon' width='$imgSizeOverride' height='$imgSizeOverride' class='badgeimg' />";
    }

    if ($justImage == false) {
        $displayable .= "$gameName $consoleStr";
    }

    return "<div class='bb_inline' onmouseover=\"Tip('$tooltip')\" onmouseout=\"UnTip()\" >" .
        "<a href='/Game/$gameID'>" .
        "$displayable" .
        "</a>" .
        "</div>";
}

function RenderMostPopularTitles($daysRange = 7, $offset = 0, $count = 10)
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

function RenderBoxArt($imagePath)
{
    echo "<div class='component gamescreenshots'>";
    echo "<h3>Box Art</h3>";
    echo "<table><tbody>";
    echo "<tr><td>";
    echo "<img src='$imagePath' style='max-width:100%;' />";
    echo "</td></tr>";
    echo "</tbody></table>";
    echo "</div>";
}

function RenderGameAlts($gameAlts)
{
    echo "<div class='component gamealts'>";
    echo "<h3>Similar Games</h3>";
    echo "Have you tried:<br>";
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

        echo "<td>";
        echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, true);
        echo "</td>";

        echo "<td>";
        echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, false, 32, true);
        echo "</td>";

        echo "<td>";
        echo "$points points<span class='TrueRatio'> ($totalTP)</span>";
        echo "</td>";

        echo "</tr>";
    }
    echo "</tbody></table>";
    echo "</div>";
}

function RenderLinkToGameForum($user, $cookie, $gameTitle, $gameID, $forumTopicID, $permissions = 0)
{
    if (isset($forumTopicID) && $forumTopicID != 0 && getTopicDetails($forumTopicID, $topicData)) {
        echo "<a href='/viewtopic.php?t=$forumTopicID'>View official forum topic for $gameTitle here</a>";
    } else {
        echo "No forum topic";
        if (isset($user) && $permissions >= Permissions::Developer) {
            echo " - <a href='/request/game/generate-forum-topic.php?u=$user&c=$cookie&g=$gameID'>Create the official forum topic for $gameTitle</a>";
        }
    }
}
