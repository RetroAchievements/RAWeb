<?php

use RA\AwardType;
use RA\Permissions;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, Permissions::Registered)) {
    if (getAccountDetails($user, $userDetails) == false) {
        // Immediate redirect if we cannot validate user!
        header("Location: " . getenv('APP_URL') . "?e=accountissue");
        exit;
    }
} else {
    // Immediate redirect if we cannot validate cookie!
    header("Location: " . getenv('APP_URL') . "?e=notloggedin");
    exit;
}

$errorCode = requestInputSanitized('e');

RenderHtmlStart();
RenderHtmlHead("Reorder Site Awards");
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<div id="mainpage">
    <div id="leftcontainer">
        <?php
        echo "<div id='warning' class='rightfloat'>Status: OK!</div>";

        echo "<h2 class='longheader'>Reorder Site Awards</h2>";
        echo "<span class='clickablebutton'><a href='/reorderSiteAwards.php'>Refresh Page</a></span><br>";

        echo "<p><b>Instructions:</b> These are your site awards as displayed on your user page. " .
            "The awards will be ordered by 'Display Order', the column found on the right, in order from smallest to greatest. " .
            "Adjust the numbers on the right to set an order for them to appear in. Setting a 'Display Order' value to -1 " .
            "will hide the site award. Any changes you make on this page will instantly " .
            "take effect on the website, but you will need to press 'Refresh Page' to see the new order on this page. " .
            "The right panel represents how the site awards will look on your user page.</p>";

        $userAwards = getUsersSiteAwards($user, true);

        $gameAwards = array_values(array_filter($userAwards, fn ($award) => $award['AwardType'] == AwardType::MASTERY && $award['ConsoleName'] != 'Events'));

        $eventAwards = array_values(array_filter($userAwards, fn ($award) => $award['AwardType'] == AwardType::MASTERY && $award['ConsoleName'] == 'Events'));

        $siteAwards = array_values(array_filter($userAwards, fn ($award) => $award['AwardType'] != AwardType::MASTERY && in_array((int) $award['AwardType'], AwardType::$active)));

        function RenderAwardOrderTable($title, $awards, &$counter)
        {
            echo "<br><h4>$title</h4>";
            echo "<table><tbody>";
            echo "<tr>";
            echo "<th>Badge</th>";
            echo "<th width=\"75%\">Site Award</th>";
            echo "<th width=\"25%\">Award Date</th>";
            echo "<th>Display Order</th>";
            echo "</tr>\n";

            foreach ($awards as $award) {
                $awardType = $award['AwardType'];
                $awardData = $award['AwardData'];
                $awardDataExtra = $award['AwardDataExtra'];
                $awardTitle = $award['Title'];
                $awardDisplayOrder = $award['DisplayOrder'];
                $awardDate = getNiceDate($award['AwardedAt']);

                sanitize_outputs(
                    $awardTitle,
                    $awardGameConsole
                );

                if ($awardType == AwardType::ACHIEVEMENT_UNLOCKS_YIELD) {
                    $awardTitle = "Achievements Earned by Others";
                } elseif ($awardType == AwardType::ACHIEVEMENT_POINTS_YIELD) {
                    $awardTitle = "Achievement Points Earned by Others";
                } elseif ($awardType == AwardType::REFERRALS) {
                    $awardTitle = "Referral Award";
                } elseif ($awardType == AwardType::PATREON_SUPPORTER) {
                    $awardTitle = "Patreon Supporter";
                }

                echo "<td>";
                RenderAward($award, 48, false);
                echo "</td>";
                echo "<td>$awardTitle</td>";
                echo "<td style=\"white-space: nowrap\"><span class='smalldate'>$awardDate</span><br></td>";
                echo "<td><input class='displayorderedit' id='$counter' type='text' value='$awardDisplayOrder' onchange=\"updateAwardDisplayOrder('$awardType', '$awardData', '$awardDataExtra', '$counter')\" size='3' /></td>";

                echo "</tr>\n";
                $counter++;
            }
            echo "</tbody></table>\n";
        }

        $counter = 0;
        if (!empty($gameAwards)) {
            RenderAwardOrderTable("Game Awards", $gameAwards, $counter);
        }

        if (!empty($eventAwards)) {
            RenderAwardOrderTable("Event Awards", $eventAwards, $counter);
        }

        if (!empty($siteAwards)) {
            RenderAwardOrderTable("Site Awards", $siteAwards, $counter);
        }

        ?>
    </div>
    <div id="rightcontainer">
        <?php RenderSiteAwards(getUsersSiteAwards($user, false)) ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
