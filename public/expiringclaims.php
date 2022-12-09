<?php

use LegacyApp\Community\Enums\ClaimFilters;
use LegacyApp\Community\Enums\ClaimSetType;
use LegacyApp\Community\Enums\ClaimSorting;
use LegacyApp\Community\Enums\ClaimSpecial;
use LegacyApp\Community\Enums\ClaimType;

authenticateFromCookie($user, $permissions, $userDetails);

$defaultFilter = ClaimFilters::Default; // Show all active claims

$username = requestInputSanitized('u', null);

$claimData = getFilteredClaims(0, $defaultFilter, ClaimSorting::FinishedDateAscending, true, $username); // Active sorted by expiring
$activeClaimCount = getActiveClaimCount();

if (!empty($gameID)) {
    getGameTitleFromID($gameID, $gameTitle, $consoleID, $consoleName, $forumTopic, $gameData);
}

sanitize_outputs(
    $gameTitle,
    $consoleName,
);

RenderContentStart("Expiring Claims");
?>
<div id='mainpage'>
    <div id='fullcontainer'>
        <?php
        echo "<h3>Expiring Claims</h3>";

        // Add username filter section if the user is in the list
        $expiringClaims = getExpiringClaim($user);
        $expired = (int) $expiringClaims["Expired"];
        $expiring = (int) $expiringClaims["Expiring"];
        if ((isset($user) || !empty($username)) && ($expired + $expiring) > 0) {
            echo "<p class='embedded'><b>User:</b> ";
            if (isset($user)) {
                if ($username == $user) {
                    echo "<b>$user</b> | ";
                } else {
                    echo "<a href='/expiringclaims.php?u=$user'>$user</a> | ";
                }
            }

            if (!empty($username) && $username !== $user) {
                echo "<b>$username</b> | ";
            }

            if (!empty($username)) {
                echo "<a href='/expiringclaims.php'>Clear Filter</a>";
            } else {
                echo "<b>Clear Filter</b>";
            }
            echo "</p>";
        }

        echo "<div class='table-wrapper'><table><tbody>";
        echo "<th colspan='2'>" . ClaimSorting::toString(ClaimSorting::UserDescending) . "</th>";
        echo "<th>" . ClaimSorting::toString(ClaimSorting::GameDescending) . "</th>";
        echo "<th>" . ClaimSorting::toString(ClaimSorting::ClaimTypeDescending) . "</th>";
        echo "<th>" . ClaimSorting::toString(ClaimSorting::SetTypeDescending) . "</th>";
        echo "<th>" . ClaimSorting::toString(ClaimSorting::SpecialDescending) . "</th>";
        echo "<th>" . ClaimSorting::toString(ClaimSorting::ClaimDateDescending) . "</th>";
        echo "<th><b>" . ClaimSorting::toString(ClaimSorting::FinishedDateDescending) . " &#9660;</b></th>";
        echo "<th>Expiration Status</th>";

        // Loop through the claims and display them in the table
        foreach ($claimData as $claim) {
            $claimUser = $claim['User'];
            echo "<tr><td class='whitespace-nowrap'>";
            echo userAvatar($claimUser, label: false);
            echo "</td>";
            echo "<td class='whitespace-nowrap'><div>";
            echo userAvatar($claimUser, icon: false);
            echo "</div></td>";

            echo "<td>";
            echo gameAvatar($claim);
            echo "</td>";
            echo "<td>" . ($claim['ClaimType'] == ClaimType::Primary ? ClaimType::toString(ClaimType::Primary) : ClaimType::toString(ClaimType::Collaboration)) . "</td>";
            echo "<td>" . ($claim['SetType'] == ClaimSetType::NewSet ? ClaimSetType::toString(ClaimSetType::NewSet) : ClaimSetType::toString(ClaimSetType::Revision)) . "</td>";
            echo "<td>" . ClaimSpecial::toString($claim['Special']) . "</td>";
            echo "<td>" . getNiceDate(strtotime($claim['Created'])) . "</td>";
            echo "<td>" . getNiceDate(strtotime($claim['DoneTime'])) . "</td>";
            $minutesLeft = $claim['MinutesLeft'];
            settype($minutesLeft, "integer");
            if ($minutesLeft < 0) {
                echo "<td><font color='red'>EXPIRED</font></td>";
            } else {
                $days = ceil($minutesLeft / (60 * 24));
                echo "<td>" . $days . " Day" . ($days == 1 ? "" : "s") . " Remaining</td>";
            }
        }
        echo "</tbody></table></div>";
        ?>
    </div>
</div>
<?php RenderContentEnd(); ?>
