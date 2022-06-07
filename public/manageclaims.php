<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

use RA\ArticleType;
use RA\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Admin)) {
    header("Location: " . getenv('APP_URL'));
    exit;
}

$gameID = requestInputSanitized('g', null, 'integer');

if (empty($gameID)) {
    header("Location: " . getenv('APP_URL'));
}

$claimData = getFilteredClaimData($gameID);
getGameMetadata($gameID, $user, $achievementData, $gameData);

$consoleName = $gameData['ConsoleName'];
$gameTitle = $gameData['Title'];
$gameIcon = $gameData['ImageIcon'];

RenderHtmlStart();
RenderHtmlHead("Manage Claims");
?>
<body>
<?php
RenderHeader($userDetails);
?>
<link rel="stylesheet" href="/vendor/jquery.datetimepicker.min.css">
<script src="/vendor/jquery.datetimepicker.full.min.js"></script>
<script>

  /**
   * Creates update post message when a claim is updated by an admin
   */
  function UpdateClaimDetails(claimID, claimUser) {
    var claimType = parseInt($('#claimType_' + claimID).val());
    var setType = parseInt($('#setType_' + claimID).val());
    var claimStatus = parseInt($('#status_' + claimID).val());
    var claimSpecial = parseInt($('#special_' + claimID).val());
    var claimDate = $('#claimDate_' + claimID).val();
    var doneDate = $('#doneDate_' + claimID).val();
    var posting = $.post('/request/set-claim/update-claim.php', {
        o: claimUser,
        i: parseInt(claimID),
        g: <?= $gameID ?>,
        c: claimType,
        s: setType,
        t: claimStatus,
        e: claimSpecial,
        d: claimDate,
        f: doneDate
    });
    posting.done(function onUpdateComplete(data) {
        location.reload();
    })
}
</script>
<div id='mainpage'>
    <div id='fullcontainer'>
        <?php
        echo "<h3>Manage Claims</h3>";
        echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameIcon, $consoleName, false, 64);

        echo "<div class='embedded mb-1'>";
        echo "<div><b>Field Values:</b></div>";
        echo "</br><u>Claim Type</u></br>";
        echo "<b>Primary</b> - Developer has the main claim on the game, this takes up a reservation spot.</br>";
        echo "<b>Collaboration</b> - Developer is collaborating with another developer, this does not take up a reservation spot.</br>";
        echo "</br><u>Claim Type</u></br>";
        echo "<b>New</b> - Claim is for a game with no core achievements.</br>";
        echo "<b>Revision</b> - Claim is for a game with core acheivements.</br>";
        echo "</br><u>Claim Status</u></br>";
        echo "<b>Active</b> - Claim is currently active.</br>";
        echo "<b>Complete</b> - Claim has been marked as complete by the developer.</br>";
        echo "<b>Dropped</b> - Claim has been dropped by the developer.</br>";
        echo "</br><u>Special</u></br>";
        echo "<b>0</b> - Standard claim taking up a reservation spot.</br>";
        echo "<b>1</b> - Own revision claim, does not take up a claim spot.</br>";
        echo "<b>2</b> - Free rollout claim, does not take up a claim spot.</br>";
        echo "<b>3</b> - Set approved for future release, does not take up a claim spot.</br>";
        echo "</br><u>Claim Date</u></br>";
        echo "Date the developer made the claim.</br>";
        echo "</br><u>Expiration / Completion / Drop Date</u></br>";
        echo "Date the claim will expire, has been completed or was dropped depending on the claim status.</br>";
        echo "</div></br>";

        echo "<div class='table-wrapper'><table><tbody>";
        echo "<th colspan='2'>User</th>";
        echo "<th>Claim Type</th>";
        echo "<th>Set Type</th>";
        echo "<th>Claim Status</th>";
        echo "<th>Special</th>";
        echo "<th>Claim Date &#9660;</th>";
        echo "<th>Expiration / Completion / Drop Date</th>";
        echo "<th>Update</th>";

        $userCount = 0;
        foreach ($claimData as $claim) {
            $claimID = $claim['ID'];
            $claimUser = $claim['User'];
            echo "<tr><td class='text-nowrap'>";
            echo GetUserAndTooltipDiv($claimUser, true);
            echo "</td>";
            echo "<td class='text-nowrap'><div class='fixheightcell' id='claimUser_$claimUser'>";
            echo GetUserAndTooltipDiv($claimUser, false);
            echo "</div></td>";

            echo "<td>";
            echo "<select id='claimType_$claimID'>";
            echo "<option " . ($claim['ClaimType'] == 0 ? "selected" : "") . " value='0'>Primary</option>";
            echo "<option " . ($claim['ClaimType'] == 0 ? "" : "selected") . " value='1'>Collaboration</option>";
            echo "</select>";
            echo "</td>";

            echo "<td>";
            echo "<select id='setType_$claimID'>";
            echo "<option " . ($claim['SetType'] == 0 ? "selected" : "") . " value='0'>New</option>";
            echo "<option " . ($claim['SetType'] == 0 ? "" : "selected") . " value='1'>Revision</option>";
            echo "</select>";
            echo "</td>";

            echo "<td>";
            echo "<select id='status_$claimID'>";
            switch ($claim['Status']) {
                case 0:
                    echo "<option selected value='0'>Active</option>";
                    echo "<option value='1'>Complete</option>";
                    echo "<option value='2'>Dropped</option>";
                    break;
                case 1:
                    echo "<option value='0'>Active</option>";
                    echo "<option selected value='1'>Complete</option>";
                    echo "<option value='2'>Dropped</option>";
                    break;
                case 2:
                    echo "<option value='0'>Active</option>";
                    echo "<option value='1'>Complete</option>";
                    echo "<option selected value='2'>Dropped</option>";
                    break;
                default:
                    echo "<option selected value='0'>Active</option>";
                    echo "<option value='1'>Complete</option>";
                    echo "<option value='2'>Dropped</option>";
                    break;
            }
            echo "</select>";
            echo "</td>";

            echo "<td>";
            echo "<select id='special_$claimID'>";
            echo "<option " . ($claim['Special'] == 0 ? "selected" : "") . " value='0'>0</option>";
            echo "<option " . ($claim['Special'] == 1 ? "selected" : "") . " value='1'>1</option>";
            echo "<option " . ($claim['Special'] == 2 ? "selected" : "") . " value='2'>2</option>";
            echo "<option " . ($claim['Special'] == 3 ? "selected" : "") . " value='3'>3</option>";
            echo "</select>";
            echo "</td>";

            echo "<td>";
            echo "<input id='claimDate_$claimID' size='18' value='" . $claim['Created'] . "'>";
            echo "</td>";

            echo "<td>";
            echo "<input id='doneDate_$claimID' size='18' value='" . $claim['DoneTime'] . "'>";
            echo "</td>";

            echo "<td><input type='submit' value='Update' onclick=\"UpdateClaimDetails('$claimID', '$claimUser');\"></td>";
        }
        echo "</tbody></table></div><br><br>";
        $numLogs = getArticleComments(ArticleType::SetClaim, $gameID, 0, 1000, $logs);
        RenderCommentsComponent($user,
            $numLogs,
            $logs,
            $gameID,
            ArticleType::SetClaim,
            $permissions
        );
        echo "</div>";
        ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>

<script>
    jQuery('[id^=claimDate]').datetimepicker({
        format: 'Y-m-d H:i:s',
        mask: true,
    });
    jQuery('[id^=doneDate]').datetimepicker({
        format: 'Y-m-d H:i:s',
        mask: true,
    });
</script>