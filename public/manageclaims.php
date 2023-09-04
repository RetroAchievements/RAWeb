<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSorting;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Site\Enums\Permissions;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Moderator)) {
    abort(401);
}

$gameID = (int) request()->query('g');
if (empty($gameID)) {
    abort(404);
}

$claimData = getFilteredClaims($gameID);
$gameData = getGameData($gameID);

$consoleName = $gameData['ConsoleName'];
$gameTitle = $gameData['Title'];
$gameIcon = $gameData['ImageIcon'];

RenderContentStart("Manage Claims - $gameTitle");
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
            case <?= ClaimStatus::InReview ?>:
                comment += "Claim Status: <?= ClaimStatus::toString(ClaimStatus::InReview) ?>. ";
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
                comment += "Special: <?= ClaimSpecial::toString(ClaimSpecial::ScheduledRelease) ?>. ";
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
        comment += "End Date: " + newDoneDate + ".";
        somethingChanged = 1;
    }

    if (somethingChanged) {
        $.post('/request/set-claim/update-claim.php', {
            game: <?= $gameID ?>,
            claim: claimID,
            claim_special: newClaimSpecial,
            claim_status: newClaimStatus,
            claim_type: newClaimType,
            set_type: newSetType,
            claimed: newClaimDate,
            claim_finish: newDoneDate,
            comment: comment
        })
            .done(function () {
                location.reload();
            });
    }
}
</script>
<article>
    <div class='navpath'>
        <?= renderGameBreadcrumb($gameData) ?>
        &raquo; <b>Manage Claims</b>
    </div>

    <?php
    echo "<h3>Manage Claims</h3>";
    echo gameAvatar($gameData, iconSize: 64);

    echo "<div class='embedded mb-1'>";
    echo "<div><b>Field Values:</b></div>";
    echo "</br><u>Claim Type</u></br>";
    echo "<b>" . ClaimType::toString(ClaimType::Primary) . "</b> - Developer has the main claim on the game, this takes up a reservation spot.</br>";
    echo "<b>" . ClaimType::toString(ClaimType::Collaboration) . "</b> - Developer is collaborating with another developer, this does not take up a reservation spot.</br>";
    echo "</br><u>Claim Type</u></br>";
    echo "<b>" . ClaimSetType::toString(ClaimSetType::NewSet) . "</b> - Claim is for a game with no core achievements.</br>";
    echo "<b>" . ClaimSetType::toString(ClaimSetType::Revision) . "</b> - Claim is for a game with core achievements.</br>";
    echo "</br><u>Claim Status</u></br>";
    echo "<b>" . ClaimStatus::toString(ClaimStatus::Active) . "</b> - Claim is currently active.</br>";
    echo "<b>" . ClaimStatus::toString(ClaimStatus::InReview) . "</b> - Claim is active and in review.</br>";
    echo "<b>" . ClaimStatus::toString(ClaimStatus::Complete) . "</b> - Claim has been marked as complete by the developer.</br>";
    echo "<b>" . ClaimStatus::toString(ClaimStatus::Dropped) . "</b> - Claim has been dropped by the developer.</br>";
    echo "</br><u>Special</u></br>";
    echo "<b>" . ClaimSpecial::toString(ClaimSpecial::None) . "</b> - Standard claim taking up a reservation spot.</br>";
    echo "<b>" . ClaimSpecial::toString(ClaimSpecial::OwnRevision) . "</b> - Own revision claim, does not take up a claim spot.</br>";
    echo "<b>" . ClaimSpecial::toString(ClaimSpecial::FreeRollout) . "</b> - Free rollout claim, does not take up a claim spot.</br>";
    echo "<b>" . ClaimSpecial::toString(ClaimSpecial::ScheduledRelease) . "</b> - Set approved for future release, does not take up a claim spot.</br>";
    echo "</br><u>Claim Date</u></br>";
    echo "Date the developer made the claim.</br>";
    echo "</br><u>End Date</u></br>";
    echo "Date the claim will expire, has been completed or was dropped depending on the claim status.</br>";
    echo "</div></br>";

    echo "<div class='table-wrapper mb-5'><table class='condensed table-highlight'><tbody>";

    echo "<tr class='do-not-highlight'>";
    echo "<th>" . ClaimSorting::toString(ClaimSorting::UserDescending) . "</th>";
    echo "<th>" . ClaimSorting::toString(ClaimSorting::ClaimTypeDescending) . "</th>";
    echo "<th>" . ClaimSorting::toString(ClaimSorting::SetTypeDescending) . "</th>";
    echo "<th>" . ClaimSorting::toString(ClaimSorting::ClaimStatusDescending) . "</th>";
    echo "<th>" . ClaimSorting::toString(ClaimSorting::SpecialDescending) . "</th>";
    echo "<th>" . ClaimSorting::toString(ClaimSorting::ClaimDateDescending) . " &#9660;</th>";
    echo "<th>End Date</th>";
    echo "<th></th>";
    echo "</tr>";

    $userCount = 0;
    foreach ($claimData as $claim) {
        $claimID = $claim['ID'];
        $claimUser = $claim['User'];
        echo "<tr>";
        echo "<td class='whitespace-nowrap'><div>";
        echo userAvatar($claimUser, iconSize: 24);
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
        if ($claimUser == $user) {
            echo "<select id='status_$claimID' disabled title='Use the claim controls on the game page to manage the status of your own claim'>";
        } else {
            echo "<select id='status_$claimID'>";
        }
        echo "<option " . ($claim['Status'] == ClaimStatus::Active ? "selected" : "") . " value=" . ClaimStatus::Active . ">" . ClaimStatus::toString(ClaimStatus::Active) . "</option>";
        echo "<option " . ($claim['Status'] == ClaimStatus::InReview ? "selected" : "") . " value=" . ClaimStatus::InReview . ">" . ClaimStatus::toString(ClaimStatus::InReview) . "</option>";
        echo "<option " . ($claim['Status'] == ClaimStatus::Complete ? "selected" : "") . " value=" . ClaimStatus::Complete . ">" . ClaimStatus::toString(ClaimStatus::Complete) . "</option>";
        echo "<option " . ($claim['Status'] == ClaimStatus::Dropped ? "selected" : "") . " value=" . ClaimStatus::Dropped . ">" . ClaimStatus::toString(ClaimStatus::Dropped) . "</option>";
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

        echo "<td><button class='btn' type='button' onclick=\"UpdateClaimDetails($claimID, '$claimUser', " . $claim['ClaimType'] . ", " . $claim['SetType'] . ", " . $claim['Status'] . ", " . $claim['Special'] . ", '" . $claim['Created'] . "', '" . $claim['DoneTime'] . "');\">Update</button></td>";
    }
    echo "</tbody></table></div>";

    $numLogs = getRecentArticleComments(ArticleType::SetClaim, $gameID, $logs);
    RenderCommentsComponent($user,
        $numLogs,
        $logs,
        $gameID,
        ArticleType::SetClaim,
        $permissions
    );
    echo "</div>";
    ?>
</article>
<?php RenderContentEnd(); ?>
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
