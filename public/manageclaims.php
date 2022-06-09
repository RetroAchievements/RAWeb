<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

use RA\ArticleType;
use RA\ClaimSetType;
use RA\ClaimSorting;
use RA\ClaimSpecial;
use RA\ClaimStatus;
use RA\ClaimType;
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
$gameData = getGameData($gameID);

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
  function UpdateClaimDetails(claimID, claimUser, claimType, setType, claimStatus, claimSpecial, claimDate, doneDate) {
    var somethingChanged = 0;
    var comment = "<?= $user ?> updated " + claimUser + "'s claim. ";

    var newClaimType = parseInt($('#claimType_' + claimID).val());
    if (newClaimType != claimType) {
        comment += "Claim Type: " + (newClaimType == <?= ClaimType::Primary ?> ? "<?= ClaimType::toString(ClaimType::Primary) ?>. " : "<?= ClaimType::toString(ClaimType::Collaboration) ?>. ")
        somethingChanged = 1;
    }

    var newSetType = parseInt($('#setType_' + claimID).val());
    if (newSetType != setType) {
        comment += "Set Type: " + (newSetType == <?= ClaimSetType::NewSet ?> ? "<?= ClaimSetType::toString(ClaimSetType::NewSet) ?>. " : "<?= ClaimSetType::toString(ClaimSetType::Revision) ?>. ")
        somethingChanged = 1;
    }

    var newClaimStatus = parseInt($('#status_' + claimID).val());
    if (newClaimStatus != claimStatus) {
        switch (newClaimStatus) {
            case <?= ClaimStatus::Active ?>:
                comment += "Claim Status: <?= ClaimStatus::toString(ClaimStatus::Active) ?>. ";
                break;
            case <?= ClaimStatus::Complete ?>:
                comment += "Claim Status: <?= ClaimStatus::toString(ClaimStatus::Complete) ?>. ";
                break;
            case <?= ClaimStatus::Dropped ?>:
                comment += "Claim Status: <?= ClaimStatus::toString(ClaimStatus::Dropped) ?>. ";
                break;
            default:
                comment += "Claim Status: <?= ClaimStatus::toString(ClaimStatus::Active) ?>. ";
                break;
        }
        somethingChanged = 1;
    }

    var newClaimSpecial = parseInt($('#special_' + claimID).val());
    if (newClaimSpecial != claimSpecial) {
        switch (newClaimSpecial) {
            case <?= ClaimSpecial::None ?>:
                comment += "Special: <?= ClaimSpecial::toString(ClaimSpecial::None) ?>. ";
                break;
            case <?= ClaimSpecial::OwnRevision ?>:
                comment += "Special: <?= ClaimSpecial::toString(ClaimSpecial::OwnRevision) ?>. ";
                break;
            case <?= ClaimSpecial::FreeRollout ?>:
                comment += "Special: <?= ClaimSpecial::toString(ClaimSpecial::FreeRollout) ?>. ";
                break;
            case <?= ClaimSpecial::ScheduledRelease ?>:
                comment += "Special: <?= ClaimSpecial::toString(ClaimSpecial::FreeRollout) ?>. ";
                break;
            default:
                comment += "Special: <?= ClaimSpecial::toString(ClaimSpecial::None) ?>. ";
                break;
        }
        somethingChanged = 1;
    }

    var newClaimDate = $('#claimDate_' + claimID).val();
    if (newClaimDate != claimDate) {
        comment += "Claim Date: " + newClaimDate + ". ";
        somethingChanged = 1;
    }

    var newDoneDate = $('#doneDate_' + claimID).val();
    if (newDoneDate != doneDate) {
        comment += "Finished date: " + newDoneDate + ".";
        somethingChanged = 1;
    }

    if (somethingChanged) {
        var posting = $.post('/request/set-claim/update-claim.php', {
            o: claimUser,
            i: claimID,
            g: <?= $gameID ?>,
            c: newClaimType,
            s: newSetType,
            t: newClaimStatus,
            e: newClaimSpecial,
            d: newClaimDate,
            f: newDoneDate,
            m: comment
        });
        posting.done(function onUpdateComplete(data) {
            location.reload();
        })
    }
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
        echo "<b>" . ClaimType::toString(ClaimType::Primary) . "</b> - Developer has the main claim on the game, this takes up a reservation spot.</br>";
        echo "<b>" . ClaimType::toString(ClaimType::Collaboration) . "</b> - Developer is collaborating with another developer, this does not take up a reservation spot.</br>";
        echo "</br><u>Claim Type</u></br>";
        echo "<b>" . ClaimSetType::toString(ClaimSetType::NewSet) . "</b> - Claim is for a game with no core achievements.</br>";
        echo "<b>" . ClaimSetType::toString(ClaimSetType::Revision) . "</b> - Claim is for a game with core acheivements.</br>";
        echo "</br><u>Claim Status</u></br>";
        echo "<b>" . ClaimStatus::toString(ClaimStatus::Active) . "</b> - Claim is currently active.</br>";
        echo "<b>" . ClaimStatus::toString(ClaimStatus::Complete) . "</b> - Claim has been marked as complete by the developer.</br>";
        echo "<b>" . ClaimStatus::toString(ClaimStatus::Dropped) . "</b> - Claim has been dropped by the developer.</br>";
        echo "</br><u>Special</u></br>";
        echo "<b>" . ClaimSpecial::toString(ClaimSpecial::None) . "</b> - Standard claim taking up a reservation spot.</br>";
        echo "<b>" . ClaimSpecial::toString(ClaimSpecial::OwnRevision) . "</b> - Own revision claim, does not take up a claim spot.</br>";
        echo "<b>" . ClaimSpecial::toString(ClaimSpecial::FreeRollout) . "</b> - Free rollout claim, does not take up a claim spot.</br>";
        echo "<b>" . ClaimSpecial::toString(ClaimSpecial::ScheduledRelease) . "</b> - Set approved for future release, does not take up a claim spot.</br>";
        echo "</br><u>Claim Date</u></br>";
        echo "Date the developer made the claim.</br>";
        echo "</br><u>Expiration / Completion / Drop Date</u></br>";
        echo "Date the claim will expire, has been completed or was dropped depending on the claim status.</br>";
        echo "</div></br>";

        echo "<div class='table-wrapper'><table><tbody>";
        echo "<th colspan='2'>" . ClaimSorting::toString(ClaimSorting::UserDescending) . "</th>";
        echo "<th>" . ClaimSorting::toString(ClaimSorting::ClaimTypeDescending) . "</th>";
        echo "<th>" . ClaimSorting::toString(ClaimSorting::SetTypeDescending) . "</th>";
        echo "<th>" . ClaimSorting::toString(ClaimSorting::ClaimStatusDescending) . "</th>";
        echo "<th>" . ClaimSorting::toString(ClaimSorting::SpecialDescending) . "</th>";
        echo "<th>" . ClaimSorting::toString(ClaimSorting::ClaimDateDescending) . " &#9660;</th>";
        echo "<th>Finished Date</th>";
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
            echo "<option " . ($claim['ClaimType'] == ClaimType::Primary ? "selected" : "") . " value=" . ClaimType::Primary . ">" . ClaimType::toString(ClaimType::Primary) . "</option>";
            echo "<option " . ($claim['ClaimType'] == ClaimType::Primary ? "" : "selected") . " value=" . ClaimType::Collaboration . ">" . ClaimType::toString(ClaimType::Collaboration) . "</option>";
            echo "</select>";
            echo "</td>";

            echo "<td>";
            echo "<select id='setType_$claimID'>";
            echo "<option " . ($claim['SetType'] == ClaimSetType::NewSet ? "selected" : "") . " value=" . ClaimSetType::NewSet . ">" . ClaimSetType::toString(ClaimSetType::NewSet) . "</option>";
            echo "<option " . ($claim['SetType'] == ClaimSetType::NewSet ? "" : "selected") . " value=" . ClaimSetType::Revision . ">" . ClaimSetType::toString(ClaimSetType::Revision) . "</option>";
            echo "</select>";
            echo "</td>";

            echo "<td>";
            echo "<select id='status_$claimID'>";
            switch ($claim['Status']) {
                case ClaimStatus::Active:
                    echo "<option selected value=" . ClaimStatus::Active . ">" . ClaimStatus::toString(ClaimStatus::Active) . "</option>";
                    echo "<option value=" . ClaimStatus::Complete . ">" . ClaimStatus::toString(ClaimStatus::Complete) . "</option>";
                    echo "<option value=" . ClaimStatus::Dropped . ">" . ClaimStatus::toString(ClaimStatus::Dropped) . "</option>";
                    break;
                case ClaimStatus::Complete:
                    echo "<option value=" . ClaimStatus::Active . ">" . ClaimStatus::toString(ClaimStatus::Active) . "</option>";
                    echo "<option selected value=" . ClaimStatus::Complete . ">" . ClaimStatus::toString(ClaimStatus::Complete) . "</option>";
                    echo "<option value=" . ClaimStatus::Dropped . ">" . ClaimStatus::toString(ClaimStatus::Dropped) . "</option>";
                    break;
                case ClaimStatus::Dropped:
                    echo "<option value=" . ClaimStatus::Active . ">" . ClaimStatus::toString(ClaimStatus::Active) . "</option>";
                    echo "<option value=" . ClaimStatus::Complete . ">" . ClaimStatus::toString(ClaimStatus::Complete) . "</option>";
                    echo "<option selected value=" . ClaimStatus::Dropped . ">" . ClaimStatus::toString(ClaimStatus::Dropped) . "</option>";
                    break;
                default:
                    echo "<option selected value=" . ClaimStatus::Active . ">" . ClaimStatus::toString(ClaimStatus::Active) . "</option>";
                    echo "<option value=" . ClaimStatus::Complete . ">" . ClaimStatus::toString(ClaimStatus::Complete) . "</option>";
                    echo "<option value=" . ClaimStatus::Dropped . ">" . ClaimStatus::toString(ClaimStatus::Dropped) . "</option>";
                    break;
            }
            echo "</select>";
            echo "</td>";

            echo "<td>";
            echo "<select id='special_$claimID'>";
            echo "<option " . ($claim['Special'] == ClaimSpecial::None ? "selected" : "") . " value=" . ClaimSpecial::None . ">" . ClaimSpecial::toString(ClaimSpecial::None) . "</option>";
            echo "<option " . ($claim['Special'] == ClaimSpecial::OwnRevision ? "selected" : "") . " value=" . ClaimSpecial::OwnRevision . ">" . ClaimSpecial::toString(ClaimSpecial::OwnRevision) . "</option>";
            echo "<option " . ($claim['Special'] == ClaimSpecial::FreeRollout ? "selected" : "") . " value=" . ClaimSpecial::FreeRollout . ">" . ClaimSpecial::toString(ClaimSpecial::FreeRollout) . "</option>";
            echo "<option " . ($claim['Special'] == ClaimSpecial::ScheduledRelease ? "selected" : "") . " value=" . ClaimSpecial::ScheduledRelease . ">" . ClaimSpecial::toString(ClaimSpecial::ScheduledRelease) . "</option>";
            echo "</select>";
            echo "</td>";

            echo "<td>";
            echo "<input id='claimDate_$claimID' size='18' value='" . $claim['Created'] . "'>";
            echo "</td>";

            echo "<td>";
            echo "<input id='doneDate_$claimID' size='18' value='" . $claim['DoneTime'] . "'>";
            echo "</td>";

            echo "<td><input type='submit' value='Update' onclick=\"UpdateClaimDetails($claimID, '$claimUser', " . $claim['ClaimType'] . ", " . $claim['SetType'] . ", " . $claim['Status'] . ", " . $claim['Special'] . ", '" . $claim['Created'] . "', '" . $claim['DoneTime'] . "');\"></td>";
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
