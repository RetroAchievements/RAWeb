<?php

use App\Platform\Enums\AchievementFlag;
use App\Platform\Models\System;
use Illuminate\Support\Facades\Blade;

$consoleList = System::get(['ID', 'Name'])->keyBy('ID')->map(fn ($system) => $system['Name']);
$consoleIDInput = (int) request()->input('z', 0);
$mobileBrowser = IsMobileBrowser();

authenticateFromCookie($user, $permissions, $userDetails);

$maxCount = 25;

$count = (int) request()->input('c', $maxCount);
$offset = (int) request()->input('o', 0);
$params = (int) request()->input('p', 3);
$dev = requestInputSanitized('d');

if ($user == null) {
    $params = 3;
}
$flags = match ($params) {
    5 => 5,
    default => 3,
};

$dev_param = null;
if ($dev != null) {
    $dev_param .= "&d=$dev";
}

$sortBy = (int) request()->input('s', 17);
$achData = getAchievementsList($user, $sortBy, $params, $count, $offset, $flags, $dev);

// Is the user looking at their own achievements list?
$isOwnEarnedAchievementsList = $user !== null && $params === 1;

$requestedConsole = "";
if ($consoleIDInput !== 0) {
    $requestedConsole = " " . $consoleList[$consoleIDInput];
}

RenderContentStart("Achievement List" . $requestedConsole);
?>
<article>
    <?php
    echo "<div class='navpath'>";
    if ($requestedConsole == "") {
        echo "<b>Achievement List</b>";
    } // NB. This will be a stub page
    echo "</div>";

    echo "<div class='detaillist'>";

    echo "<h3>";
    if ($dev != null) {
        echo "<a href='/user/$dev'>$dev</a>'s ";
    }
    echo "Achievement List</h3>";

    echo "<div class='flex flex-wrap justify-between'>";
    echo "<div>";

    echo $params !== AchievementFlag::OfficialCore ? "<a href='/achievementList.php?s=$sortBy&p=" . AchievementFlag::OfficialCore . "$dev_param'>" : "<b>";
    echo "Achievements in Core Sets";
    echo $params !== AchievementFlag::OfficialCore ? "</a>" : "</b>";
    echo "<br>";

    if ($user !== null) {
        echo $params !== AchievementFlag::Unofficial ? "<a href='/achievementList.php?s=$sortBy&p=" . AchievementFlag::Unofficial . "$dev_param'>" : "<b>";
        echo "Achievements in Unofficial Sets";
        echo $params !== AchievementFlag::Unofficial ? "</a>" : "</b>";
        echo "<br>";

        echo $params !== 1 ? "<a href='/achievementList.php?s=$sortBy&p=1$dev_param'>" : "<b>";
        echo "My Unlocked Achievements";
        echo $params !== 1 ? "</a>" : "</b>";
        echo "<br>";

        // echo $params !== 2 ? "<a href='/achievementList.php?s=$sortBy&p=2$dev_param'>" : "<b>";
        // echo "Achievements I haven't yet unlocked";
        // echo $params !== 2 ? "</a>" : "</b>";
        // echo "<br>";
    }
    echo "</div>";

    if ($user !== null) {
        echo "<div>";
        echo "Filter by developer:<br>";
        echo "<form action='/achievementList.php'>";
        echo "<input type='hidden' name='s' value='$sortBy'>";
        echo "<input type='hidden' name='p' value='$params'>";
        echo "<input size='28' name='d' type='text' value='$dev'>";
        echo "&nbsp;<button class='btn'>Select</button>";
        echo "</form>";
        echo "</div>";
    }

    echo "</div>";

    echo "<div class='float-right'>* = ordered by</div>";
    echo "<br style='clear:both;' />";

    echo "<div class='table-wrapper'><table class='table-highlight'><tbody>";

    $sort1 = ($sortBy == 1) ? 11 : 1;
    $sort2 = ($sortBy == 2) ? 12 : 2;
    $sort3 = ($sortBy == 13) ? 3 : 13;
    $sort4 = ($sortBy == 4) ? 14 : 4;
    $sort5 = ($sortBy == 5) ? 15 : 5;
    $sort6 = ($sortBy == 6) ? 16 : 6;
    $sort7 = ($sortBy == 17) ? 7 : 17;
    $sort8 = ($sortBy == 18) ? 8 : 18;
    $sort9 = ($sortBy == 19) ? 9 : 19;

    $mark1 = ($sortBy % 10 == 1) ? '&nbsp;*' : '';
    $mark2 = ($sortBy % 10 == 2) ? '&nbsp;*' : '';
    $mark3 = ($sortBy % 10 == 3) ? '&nbsp;*' : '';
    $mark4 = ($sortBy % 10 == 4) ? '&nbsp;*' : '';
    $mark5 = ($sortBy % 10 == 5) ? '&nbsp;*' : '';
    $mark6 = ($sortBy % 10 == 6) ? '&nbsp;*' : '';
    $mark7 = ($sortBy % 10 == 7) ? '&nbsp;*' : '';
    $mark8 = ($sortBy % 10 == 8) ? '&nbsp;*' : '';
    $mark9 = ($sortBy % 10 == 9) ? '&nbsp;*' : '';

    echo "<tr class='do-not-highlight'>";

    echo "<th class='pr-0'></th>";
    echo "<th>";
    echo "<a href='/achievementList.php?s=$sort1&p=$params$dev_param'>Title</a>$mark1";
    echo " / ";
    echo "<a href='/achievementList.php?s=$sort2&p=$params$dev_param'>Description</a>$mark2";
    echo "</th>";

    if (!$mobileBrowser) {
        echo "<th class='whitespace-nowrap'>";
        echo "<a href='/achievementList.php?s=$sort3&p=$params$dev_param'>Points</a>$mark3 ";
        echo "<br><span class='TrueRatio'>(<a href='/achievementList.php?s=$sort4&p=$params$dev_param'>RetroPoints</a>$mark4)</span>";
        echo "</th>";
        echo "<th><a href='/achievementList.php?s=$sort5&p=$params$dev_param'>Author</a>$mark5</th>";
    }

    echo "<th><a href='/achievementList.php?s=$sort6&p=$params$dev_param'>Game</a>$mark6</th>";

    if (!$isOwnEarnedAchievementsList) {
        echo "<th><a href='/achievementList.php?s=$sort7&p=$params$dev_param'>Added</a>$mark7</th>";

        if (!$mobileBrowser) {
            echo "<th><a href='/achievementList.php?s=$sort8&p=$params$dev_param'>Modified</a>$mark8</th>";
        }
    } else {
        echo "<th><a href='/achievementList.php?s=$sort9&p=$params$dev_param'>Awarded</a>$mark9</th>";
    }

    echo "</tr>";

    foreach ($achData as $achEntry) {
        $achID = $achEntry['ID'];
        $achTitle = $achEntry['AchievementTitle'];
        $achDesc = $achEntry['Description'];
        $achPoints = $achEntry['Points'];
        $achTruePoints = $achEntry['TrueRatio'];
        $achAuthor = $achEntry['Author'];
        $achDateCreated = $achEntry['DateCreated'];
        $achDateModified = $achEntry['DateModified'];
        $achBadgeName = $achEntry['BadgeName'];
        $gameID = $achEntry['GameID'];
        $gameIcon = $achEntry['GameIcon'];
        $gameTitle = $achEntry['GameTitle'];
        $consoleID = $achEntry['ConsoleID'];
        $consoleName = $achEntry['ConsoleName'];
        $achAwardedDate = isset($achEntry['AwardedDate']) ? $achEntry['AwardedDate'] : "";

        sanitize_outputs(
            $achTitle,
            $achDesc,
            $achAuthor,
            $gameTitle,
            $consoleName
        );

        echo "<tr>";

        echo "<td class='pr-0'>";
        echo achievementAvatar($achEntry, label: false);
        echo "</td>";
        echo "<td class='w-full xl:w-[50%]'>";
        echo achievementAvatar($achEntry, icon: false);
        echo "<br>$achDesc";
        echo "</td>";

        if (!$mobileBrowser) {
            echo "<td>";
            echo "$achPoints ";
            echo Blade::render("<x-points-weighted-container>(" . localized_number($achTruePoints) . ")</x-points-weighted-container>");
            echo "</td>";

            echo "<td>";
            echo userAvatar($achAuthor, label: false);
            echo "</td>";
        }

        echo "<td>";
        echo gameAvatar($achEntry, label: false);
        echo "</td>";

        if (!$isOwnEarnedAchievementsList) {
            echo "<td>";
            echo "<span class='smalldate'>" . getNiceDate(strtotime($achDateCreated)) . "</span>";
            echo "</td>";

            if (!$mobileBrowser) {
                echo "<td>";
                echo "<span class='smalldate'>" . getNiceDate(strtotime($achDateModified)) . "</span>";
                echo "</td>";
            }
        } else {
            $renderAwardedDate = "Unknown";
            if (strlen($achAwardedDate) > 0) {
                $renderAwardedDate = getNiceDate(strtotime($achAwardedDate));
            }

            echo "<td>";
            echo "<span class='smalldate'>$renderAwardedDate</span>";
            echo "</td>";
        }

        echo "</tr>";
    }

    echo "</tbody></table></div>";
    echo "</div>";

    echo "<div class='text-right'>";
    if ($offset > 0) {
        $prevOffset = $offset - $maxCount;
        echo "<a href='/achievementList.php?s=$sortBy&o=$prevOffset&p=$params$dev_param'>&lt; Previous $maxCount</a> - ";
    }
    if ($achData->count() === $maxCount) {
        // Max number fetched, i.e. there are more. Can goto next 25.
        $nextOffset = $offset + $maxCount;
        echo "<a href='/achievementList.php?s=$sortBy&o=$nextOffset&p=$params$dev_param'>Next $maxCount &gt;</a>";
    }
    echo "</div>";
    ?>
</article>
<?php RenderContentEnd(); ?>
