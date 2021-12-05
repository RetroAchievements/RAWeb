<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions)) {
    if (getAccountDetails($user, $userDetails) == false) {
        //  Immediate redirect if we cannot validate user!
        header("Location: " . getenv('APP_URL') . "?e=accountissue");
        exit;
    }
} else {
    //  Immediate redirect if we cannot validate cookie!
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
            "The right panel represents how the site awards will look on your user page.</p><br>";

        echo "<table><tbody>";
        echo "<tr>";
        echo "<th>Badge</th>";
        echo "<th>Site Award</th>";
        echo "<th>Award Date</th>";
        echo "<th>Display Order</th>";
        echo "</tr>";

        $imageSize = 48;

        $counter = 0;
        foreach (getUsersSiteAwards($user, true) as $elem) {
            $awardType = $elem['AwardType'];
            $awardData = $elem['AwardData'];
            $awardDataExtra = $elem['AwardDataExtra'];
            $awardTitle = $elem['Title'];
            $awardGameConsole = $elem['ConsoleName'];
            $awardGameImage = $elem['ImageIcon'];
            $awardDisplayOrder = $elem['DisplayOrder'];
            $awardDate = getNiceDate($elem['AwardedAt']);
            $awardButGameIsIncomplete = (isset($elem['Incomplete']) && $elem['Incomplete'] == 1);
            $imgclass = 'badgeimg siteawards';

            sanitize_outputs(
                $awardTitle,
                $awardGameConsole
            );

            settype($awardType, 'integer');

            if ($awardType == 1) {
                if ($awardDataExtra == '1') {
                    $tooltip = "MASTERED $awardTitle ($awardGameConsole)";
                    $imgclass = 'goldimage';
                } else {
                    $tooltip = "Completed $awardTitle ($awardGameConsole)";
                }

                if ($awardButGameIsIncomplete) {
                    $tooltip .= "...<br>but more achievements have been added!<br>Click here to find out what you're missing!";
                }

                $imagepath = $awardGameImage;
                $linkdest = "/game/$awardData";
            } elseif ($awardType == 2) { //    Developed a number of earned achievements
                $tooltip = "Awarded for being a hard-working developer and producing achievements that have been earned over " . RA\AwardThreshold::DEVELOPER_COUNT_BOUNDARIES[$awardData] . " times!";
                $awardTitle = "Achievements Earned by Others";
                $imagepath = getenv('ASSET_URL') . "/Images/_Trophy" . RA\AwardThreshold::DEVELOPER_COUNT_BOUNDARIES[$awardData] . ".png";
            } elseif ($awardType == 3) { //    Yielded an amount of points earned by players
                $tooltip = "Awarded for producing many valuable achievements, providing over " . RA\AwardThreshold::DEVELOPER_POINT_BOUNDARIES[$awardData] . " points to the community!";
                $awardTitle = "Achievement Points Earned by Others";

                if ($awardData == 0) {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00133.png";
                } elseif ($awardData == 1) {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00134.png";
                } elseif ($awardData == 2) {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00137.png";
                } elseif ($awardData == 3) {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00135.png";
                } elseif ($awardData == 4) {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00136.png";
                } else {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00136.png";
                }
            } elseif ($awardType == 4) { //    Referrals
                $tooltip = "Referred $awardData members";
                $awardTitle = "Referral Award";

                if ($awardData < 2) {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00083.png";
                } elseif ($awardData < 3) {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00083.png";
                } elseif ($awardData < 5) {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00083.png";
                } elseif ($awardData < 10) {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00083.png";
                } elseif ($awardData < 15) {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00083.png";
                } else {
                    $imagepath = getenv('ASSET_URL') . "/Badge/00083.png";
                }
            } elseif ($awardType == 5) { //    Signed up for facebook!
                $tooltip = "Awarded for associating their account with Facebook! Thanks for spreading the word!";
                $awardTitle = "Facebook Association";
                $imagepath = getenv('ASSET_URL') . "/Images/_FBAssoc.png";
            } elseif ($awardType == 6) {  //  Patreon Supporter
                $tooltip = 'Awarded for being a Patreon supporter! Thank-you so much for your support!';
                $awardTitle = "Patreon Supporter";
                $imagepath = getenv('ASSET_URL') . '/Badge/PatreonBadge.png';
            } else {
                // error_log("Unknown award type" . $awardType);
                continue;
            }

            $tooltip .= "\r\nAwarded on $awardDate";

            echo "<td><img class=\"$imgclass\" alt=\"$tooltip\" title=\"$tooltip\" style='float:middle;' src='$imagepath' width='$imageSize' height='$imageSize' /></td>";
            echo "<td>$awardTitle</td>";
            echo "<td><span class='smalldate'>$awardDate</span><br></td>";
            echo "<td><input class='displayorderedit' id='$counter' type='text' value='$awardDisplayOrder' onchange=\"updateAwardDisplayOrder('$awardType', '$awardData', '$awardDataExtra', '$counter')\" size='3' /></td>";

            echo "</tr>";
            $counter++;
        }
        echo "</tbody></table>";
        ?>
    </div>
    <div id="rightcontainer">
        <?php RenderSiteAwards(getUsersSiteAwards($user, false)) ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
