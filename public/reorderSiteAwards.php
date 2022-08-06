<?php

use RA\AwardType;
use RA\Permissions;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    // Immediate redirect if we cannot validate cookie!
    header("Location: " . getenv('APP_URL') . "?e=notloggedin");
    exit;
}

$errorCode = requestInputSanitized('e');

RenderHtmlStart();
RenderHtmlHead("Reorder Site Awards");
?>
<body>
<?php RenderHeader($userDetails); ?>
<script>
function updateAwardDisplayOrder(awardType, awardData, awardDataExtra, objID) {
  var inputText = $('#' + objID).val();
  var inputNum = Math.max(-1, Math.min(Number(inputText), 10000));
  showStatusMessage('Updating...');
  var posting = $.post(
    '/request/user/update-site-award.php',
    {
      t: awardType,
      d: awardData,
      e: awardDataExtra,
      v: inputNum,
    }
  );
  posting.done(function (data) {
    if (data !== 'OK') {
      showStatusFailure('Error: ' + data);
    } else {
      showStatusSuccess('Succeeded');
    }
  });
}
</script>
<div id="mainpage">
    <div id="leftcontainer">
        <?php
        echo "<h2 class='longheader'>Reorder Site Awards</h2>";
        echo "<span class='clickablebutton'><a href='/reorderSiteAwards.php'>Refresh Page</a></span><br>";

        echo "<p><b>Instructions:</b> These are your site awards as displayed on your user page. " .
            "The awards will be ordered by 'Display Order', the column found on the right, in order from smallest to greatest. " .
            "Adjust the numbers on the right to set an order for them to appear in. Setting a 'Display Order' value to -1 " .
            "will hide the site award. Any changes you make on this page will instantly " .
            "take effect on the website, but you will need to press 'Refresh Page' to see the new order on this page. " .
            "The right panel represents how the site awards will look on your user page.</p>";

        $userAwards = getUsersSiteAwards($user, true);

        [$gameAwards, $eventAwards, $siteAwards] = SeparateAwards($userAwards);

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

                if ($awardType == AwardType::AchievementUnlocksYield) {
                    $awardTitle = "Achievements Earned by Others";
                } elseif ($awardType == AwardType::AchievementPointsYield) {
                    $awardTitle = "Achievement Points Earned by Others";
                } elseif ($awardType == AwardType::Referrals) {
                    $awardTitle = "Referral Award";
                } elseif ($awardType == AwardType::PatreonSupporter) {
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

        RenderStatusWidget();

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
        <?php RenderSiteAwards(getUsersSiteAwards($user)) ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
