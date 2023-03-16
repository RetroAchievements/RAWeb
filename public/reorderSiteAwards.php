<?php

use LegacyApp\Community\Enums\AwardType;
use LegacyApp\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
}

RenderContentStart("Reorder Site Awards");
?>
<script>
function updateAllAwardsDisplayOrder() {
    showStatusMessage('Updating...');

    const awards = [];

    $('.displayorderedit').each(function (index, element) {
        const row = $(element).closest('tr');
        const awardType = row.find("input[type='hidden'][name='type']").val();
        const awardData = row.find("input[type='hidden'][name='data']").val();
        const awardDataExtra = row.find("input[type='hidden'][name='extra']").val();
        const displayOrder = parseInt($(element).val(), 10);

        awards.push({
            type: awardType,
            data: awardData,
            extra: awardDataExtra,
            number: displayOrder
        });
    });

    $.post('/request/user/update-site-awards.php', { awards })
        .done(function (response) {
            showStatusMessage('Awards updated successfully');
            $('#rightcontainer').html(response.updatedAwardsHTML);
        })
        .fail(function () {
            showStatusMessage('Error updating awards');
        });
}
</script>
<div id="mainpage">
    <div id="leftcontainer">
        <?php
        echo "<h2>Reorder Site Awards</h2>";

        echo "<p class='embedded'>";
        echo "To rearrange your site awards, adjust the 'Display Order' value in the rightmost column of " .
            "each award row. The awards will appear on your user page in ascending order according to their " .
            "'Display Order' values. To hide an award, set its 'Display Order' value to -1. Don't forget to save " .
            "your changes by clicking the 'Save' button. Your updates will be immediately reflected on your user page.";
        echo "</p>";

        $userAwards = getUsersSiteAwards($user, true);

        [$gameAwards, $eventAwards, $siteAwards] = SeparateAwards($userAwards);

        function RenderAwardOrderTable(string $title, array $awards, int &$counter): void
        {
            echo "<br><h4>$title</h4>";
            echo "<table class='table-highlight mb-2'><tbody>";

            echo "<tr class='do-not-highlight'>";
            echo "<th>Badge</th>";
            echo "<th width=\"75%\">Site Award</th>";
            echo "<th width=\"25%\">Award Date</th>";
            echo "<th>Display Order</th>";
            echo "</tr>\n";

            // "Game Awards" -> "game"
            $humanReadableAwardType = strtolower(strtok($title, " "));

            foreach ($awards as $award) {
                $awardType = $award['AwardType'];
                $awardData = $award['AwardData'];
                $awardDataExtra = $award['AwardDataExtra'];
                $awardTitle = $award['Title'];
                $awardDisplayOrder = $award['DisplayOrder'];
                $awardDate = getNiceDate($award['AwardedAt']);

                sanitize_outputs(
                    $awardTitle,
                    $awardGameConsole,
                    $awardType,
                    $awardData,
                    $awardDataExtra,
                );

                if ($awardType == AwardType::AchievementUnlocksYield) {
                    $awardTitle = "Achievements Earned by Others";
                } elseif ($awardType == AwardType::AchievementPointsYield) {
                    $awardTitle = "Achievement Points Earned by Others";
                } elseif ($awardType == AwardType::PatreonSupporter) {
                    $awardTitle = "Patreon Supporter";
                }

                echo "<tr>";
                echo "<td>";
                RenderAward($award, 48, false);
                echo "</td>";
                echo "<td>$awardTitle</td>";
                echo "<td style=\"white-space: nowrap\"><span class='smalldate'>$awardDate</span><br></td>";
                echo "<td><input class='displayorderedit' data-award-type='$humanReadableAwardType' id='$counter' type='text' value='$awardDisplayOrder' size='3' /></td>";
                echo "<input type='hidden' name='type' value='$awardType'>";
                echo "<input type='hidden' name='data' value='$awardData'>";
                echo "<input type='hidden' name='extra' value='$awardDataExtra'>";                

                echo "</tr>\n";
                $counter++;
            }
            echo "</tbody></table>\n";

            echo "<div class='flex w-full justify-end'>";
            echo "<button onclick='updateAllAwardsDisplayOrder()'>Save</button>";
            echo "</div>";
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
        <?php RenderSiteAwards(getUsersSiteAwards($user)) ?>
    </div>
</div>
<?php RenderContentEnd(); ?>
