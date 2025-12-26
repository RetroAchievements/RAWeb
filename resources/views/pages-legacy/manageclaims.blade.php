<?php

use App\Community\Enums\ArticleType;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSorting;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Enums\Permissions;
use App\Models\AchievementSetClaim;
use App\Models\User;

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    abort(401);
}

/** @var User $userModel */
$userModel = Auth::user();
abort_if(!$userModel->can('updateAny', AchievementSetClaim::class), 401);

$gameID = (int) request()->query('g');
if (empty($gameID)) {
    abort(404);
}

$claimData = getFilteredClaims($gameID);
$gameData = getGameData($gameID);

$consoleName = $gameData['ConsoleName'];
$gameTitle = $gameData['Title'];
$gameIcon = $gameData['ImageIcon'];
?>
<x-app-layout pageTitle="Manage Claims - {{ $gameTitle }}">
<link rel="stylesheet" href="/vendor/jquery.datetimepicker.min.css">
<script src="/vendor/jquery.datetimepicker.full.min.js"></script>
<script>

  /**
   * Creates update post message when a claim is updated by an admin
   */
  function UpdateClaimDetails(claimID, claimUser, claimType, setType, claimStatus, claimSpecial, claimDate, doneDate) {
    var somethingChanged = 0;

    var newClaimType = $('#claimType_' + claimID).val();
    if (newClaimType !== claimType) {
        somethingChanged = 1;
    }

    var newSetType = $('#setType_' + claimID).val();
    if (newSetType !== setType) {
        somethingChanged = 1;
    }

    var newClaimStatus = $('#status_' + claimID).val();
    if (newClaimStatus !== claimStatus) {
        somethingChanged = 1;
    }

    var newClaimSpecial = $('#special_' + claimID).val();
    if (newClaimSpecial !== claimSpecial) {
        somethingChanged = 1;
    }

    var newClaimDate = $('#claimDate_' + claimID).val();
    if (newClaimDate !== claimDate) {
        somethingChanged = 1;
    }

    var newDoneDate = $('#doneDate_' + claimID).val();
    if (newDoneDate !== doneDate) {
        somethingChanged = 1;
    }

    if (somethingChanged) {
        $.post("{{ route('achievement-set-claim.update', 99999999) }}".replace("99999999", claimID), {
            special: newClaimSpecial,
            status: newClaimStatus,
            type: newClaimType,
            set_type: newSetType,
            claimed: newClaimDate,
            finished: newDoneDate
        })
            .done(function () {
                location.reload();
            });
    }
}
</script>
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
    echo "<b>" . ClaimType::Primary->label() . "</b> - Developer has the main claim on the game, this takes up a reservation spot.</br>";
    echo "<b>" . ClaimType::Collaboration->label() . "</b> - Developer is collaborating with another developer, this does not take up a reservation spot.</br>";
    echo "</br><u>Claim Type</u></br>";
    echo "<b>" . ClaimSetType::NewSet->label() . "</b> - Claim is for a game with no core achievements.</br>";
    echo "<b>" . ClaimSetType::Revision->label() . "</b> - Claim is for a game with core achievements.</br>";
    echo "</br><u>Claim Status</u></br>";
    echo "<b>" . ClaimStatus::Active->label() . "</b> - Claim is currently active.</br>";
    echo "<b>" . ClaimStatus::InReview->label() . "</b> - Claim is active and in review.</br>";
    echo "<b>" . ClaimStatus::Complete->label() . "</b> - Claim has been marked as complete by the developer.</br>";
    echo "<b>" . ClaimStatus::Dropped->label() . "</b> - Claim has been dropped by the developer.</br>";
    echo "</br><u>Special</u></br>";
    echo "<b>" . ClaimSpecial::None->label() . "</b> - Standard claim taking up a reservation spot.</br>";
    echo "<b>" . ClaimSpecial::OwnRevision->label() . "</b> - Own revision claim, does not take up a claim spot.</br>";
    echo "<b>" . ClaimSpecial::FreeRollout->label() . "</b> - Free rollout claim, does not take up a claim spot.</br>";
    echo "<b>" . ClaimSpecial::ScheduledRelease->label() . "</b> - Set approved for future release, does not take up a claim spot.</br>";
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
        echo "<option " . ($claim['ClaimType'] == ClaimType::Primary->value ? "selected" : "") . " value=" . ClaimType::Primary->value . ">" . ClaimType::Primary->label() . "</option>";
        echo "<option " . ($claim['ClaimType'] == ClaimType::Primary->value ? "" : "selected") . " value=" . ClaimType::Collaboration->value . ">" . ClaimType::Collaboration->label() . "</option>";
        echo "</select>";
        echo "</td>";

        echo "<td>";
        echo "<select id='setType_$claimID'>";
        echo "<option " . ($claim['SetType'] == ClaimSetType::NewSet->value ? "selected" : "") . " value=" . ClaimSetType::NewSet->value . ">" . ClaimSetType::NewSet->label() . "</option>";
        echo "<option " . ($claim['SetType'] == ClaimSetType::NewSet->value ? "" : "selected") . " value=" . ClaimSetType::Revision->value . ">" . ClaimSetType::Revision->label() . "</option>";
        echo "</select>";
        echo "</td>";

        echo "<td>";
        if ($claimUser == $user) {
            echo "<select id='status_$claimID' disabled title='Use the claim controls on the game page to manage the status of your own claim'>";
        } else {
            echo "<select id='status_$claimID'>";
        }
        echo "<option " . ($claim['Status'] == ClaimStatus::Active->value ? "selected" : "") . " value=" . ClaimStatus::Active->value . ">" . ClaimStatus::Active->label() . "</option>";
        echo "<option " . ($claim['Status'] == ClaimStatus::InReview->value ? "selected" : "") . " value=" . ClaimStatus::InReview->value . ">" . ClaimStatus::InReview->label() . "</option>";
        echo "<option " . ($claim['Status'] == ClaimStatus::Complete->value ? "selected" : "") . " value=" . ClaimStatus::Complete->value . ">" . ClaimStatus::Complete->label() . "</option>";
        echo "<option " . ($claim['Status'] == ClaimStatus::Dropped->value ? "selected" : "") . " value=" . ClaimStatus::Dropped->value . ">" . ClaimStatus::Dropped->label() . "</option>";
        echo "</select>";
        echo "</td>";

        echo "<td>";
        echo "<select id='special_$claimID'>";
        echo "<option " . ($claim['Special'] == ClaimSpecial::None->value ? "selected" : "") . " value=" . ClaimSpecial::None->value . ">" . ClaimSpecial::None->label() . "</option>";
        echo "<option " . ($claim['Special'] == ClaimSpecial::OwnRevision->value ? "selected" : "") . " value=" . ClaimSpecial::OwnRevision->value . ">" . ClaimSpecial::OwnRevision->label() . "</option>";
        echo "<option " . ($claim['Special'] == ClaimSpecial::FreeRollout->value ? "selected" : "") . " value=" . ClaimSpecial::FreeRollout->value . ">" . ClaimSpecial::FreeRollout->label() . "</option>";
        echo "<option " . ($claim['Special'] == ClaimSpecial::ScheduledRelease->value ? "selected" : "") . " value=" . ClaimSpecial::ScheduledRelease->value . ">" . ClaimSpecial::ScheduledRelease->label() . "</option>";
        echo "</select>";
        echo "</td>";

        echo "<td>";
        echo "<input id='claimDate_$claimID' size='18' value='" . $claim['Created'] . "'>";
        echo "</td>";

        echo "<td>";
        echo "<input id='doneDate_$claimID' size='18' value='" . $claim['DoneTime'] . "'>";
        echo "</td>";

        echo "<td><button class='btn' type='button' onclick=\"UpdateClaimDetails($claimID, '$claimUser', '" . $claim['ClaimType'] . "', '" . $claim['SetType'] . "', '" . $claim['Status'] . "', '" . $claim['Special'] . "', '" . $claim['Created'] . "', '" . $claim['DoneTime'] . "');\">Update</button></td>";
    }
    echo "</tbody></table></div>";

    echo Blade::render("<x-comment.list :articleType=\"\$articleType\" :articleId=\"\$articleId\" />",
        ['articleType' => ArticleType::SetClaim, 'articleId' => $gameID]
    );

    echo "</div>";
    ?>
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
</x-app-layout>
