<?php

use RA\ClaimFilters;
use RA\ClaimSetType;
use RA\ClaimSorting;
use RA\ClaimSpecial;
use RA\ClaimStatus;
use RA\ClaimType;
use RA\Permissions;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

authenticateFromCookie($user, $permissions, $userDetails);

$defaultFilter = ClaimFilters::Default; // Show all active claims
$defaultSorting = ClaimSorting::ClaimDateDescending; // Sort by claim date
$maxCount = 50;
$offset = 0;
$totalClaims = 0;

$errorCode = requestInputSanitized('e');
$type = requestInputSanitized('t', 0, 'integer');
$username = requestInputSanitized('u', null);
$claimFilters = requestInputSanitized('f', $defaultFilter, 'integer');
$sortType = requestInputSanitized('s', $defaultSorting, 'integer');
$gameID = requestInputSanitized('g', null, 'integer');
$limit = requestInputSanitized('c', $maxCount, 'integer');
$offset = requestInputSanitized('o', $offset, 'integer');

if ($type == 0) { // Get general data
    $claimData = getFilteredClaimData($gameID, $claimFilters, $sortType, false, $username, false, $offset, $limit);
    $totalClaims = getFilteredClaimData($gameID, $claimFilters, $sortType, false, $username, true);
} else { // Get expiring data
    $claimData = getFilteredClaimData(0, $defaultFilter, ClaimSorting::FinishedDateAscending, true, $username, false); // Active sorted by expiring
}
$activeClaimCount = getActiveClaimCount();

if (!empty($gameID)) {
    getGameTitleFromID($gameID, $gameTitle, $consoleID, $consoleName, $forumTopic, $gameData);
}

sanitize_outputs(
    $gameTitle,
    $consoleName,
);

RenderHtmlStart();
if ($type == 0) {
    RenderHtmlHead("Claim List");
} else {
    RenderHtmlHead("Expiring Claims");
}
?>
<body>
<?php
RenderHeader($userDetails);
?>
<div id='mainpage'>
    <div id='fullcontainer'>
        <?php
        RenderErrorCodeWarning($errorCode);
        if ($type == 0) { // Show standard claim list
            echo "<h3>Claim List - $activeClaimCount Active Claims</h3>";
            echo "<h4>Filters - $totalClaims Claims Filtered</h4>";
            echo "<div class='embedded mb-1'>";

            $createLink = function ($flag, $value, $flag2 = null, $value2 = null) use ($sortType, $claimFilters, $username, $gameID) {
                $appendParam = function (&$link, $param, $fallback, $default) use ($flag, $value, $flag2, $value2) {
                    $param_value = ($flag == $param) ? $value : (($flag2 == $param) ? $value2 : $fallback);
                    if ($param_value != $default) {
                        $link .= str_contains($link, '?') ? '&' : '?';
                        $link .= $param . '=' . $param_value;
                    }
                };

                $link = "/claimlist.php";
                $appendParam($link, 's', $sortType, 8);
                $appendParam($link, 'f', $claimFilters, ClaimFilters::Default);
                $appendParam($link, 'u', $username, null);
                $appendParam($link, 'g', $gameID, null);
                return $link;
            };

            $linkFilter = function (string $label, int $claimFilter) use ($claimFilters, $createLink) {
                if ($claimFilters & $claimFilter) {
                    return "<b><a href='" . $createLink('f', $claimFilters & ~$claimFilter) . "'>*$label</a></b>";
                } else {
                    return "<a href='" . $createLink('f', $claimFilters | $claimFilter) . "'>$label</a>";
                }
            };

            $linkSorting = function (string $label, int $sort1, int $sort2) use ($sortType, $createLink) {
                $colspan = '';
                if ($sort1 == ClaimSorting::UserDescending) {
                    $colspan = " colspan='2'";
                }

                if (($sortType % 10) == $sort1) { // if on the current sort header
                    if ($sortType == $sort2) {
                        return "<th $colspan><b><a href='" . $createLink('s', $sort1) . "'>$label &#9650;</a></b></th>"; // Ascending
                    } else {
                        return "<th $colspan><b><a href='" . $createLink('s', $sort2) . "'>$label &#9660;</a></b></th>"; // Descending
                    }
                } else {
                    return "<th $colspan><a href='" . $createLink('s', $sort1) . "'>$label</a></th>";
                }
            };

            // Claim Type filter
            echo "<div>";
            echo "<b>Claim Type:</b> ";
            echo $linkFilter(ClaimType::toString(ClaimType::Primary), ClaimFilters::PrimaryClaim) . ' | ';
            echo $linkFilter(ClaimType::toString(ClaimType::Collaboration), ClaimFilters::CollaborationClaim);
            echo "</div>";

            // Set Type filter
            echo "<div>";
            echo "<b>Set Type:</b> ";
            echo $linkFilter(ClaimSetType::toString(ClaimSetType::NewSet), ClaimFilters::NewSetClaim) . ' | ';
            echo $linkFilter(ClaimSetType::toString(ClaimSetType::Revision), ClaimFilters::RevisionClaim);
            echo "</div>";

            // Claim Status filter
            echo "<div>";
            echo "<b>Claim Status:</b> ";
            echo $linkFilter(ClaimStatus::toString(ClaimStatus::Active), ClaimFilters::ActiveClaim) . ' | ';
            echo $linkFilter(ClaimStatus::toString(ClaimStatus::Complete), ClaimFilters::CompleteClaim) . ' | ';
            echo $linkFilter(ClaimStatus::toString(ClaimStatus::Dropped), ClaimFilters::DroppedClaim);
            echo "</div>";

            // Special filter
            echo "<div>";
            echo "<b>Special:</b> ";
            echo $linkFilter(ClaimSpecial::toString(ClaimSpecial::None), ClaimFilters::SpecialNone) . ' | ';
            echo $linkFilter(ClaimSpecial::toString(ClaimSpecial::OwnRevision), ClaimFilters::SpecialOwnRevision) . ' | ';
            echo $linkFilter(ClaimSpecial::toString(ClaimSpecial::FreeRollout), ClaimFilters::SpecialFreeRollout) . ' | ';
            echo $linkFilter(ClaimSpecial::toString(ClaimSpecial::ScheduledRelease), ClaimFilters::SpecialScheduledRelease);
            echo "</div>";

            // Developer Status filter
            echo "<div>";
            echo "<b>Developer Status:</b> ";
            echo $linkFilter(Permissions::toString(Permissions::Developer), ClaimFilters::DeveloperClaim) . ' | ';
            echo $linkFilter(Permissions::toString(Permissions::JuniorDeveloper), ClaimFilters::JuniorDeveloperClaim);
            echo "</div>";

            // Clear Filter
            if ($claimFilters != $defaultFilter) {
                echo "<div>";
                echo "<a href='" . $createLink('f', $defaultFilter) . "'>Clear Filter</a>";
                echo "</div>";
            }
            echo "</div>";

            // Username filter
            if (isset($user) || !empty($username)) {
                echo "<p><b>User:</b> ";
                if (isset($user)) {
                    if ($username == $user) {
                        echo "<b>$user</b> | ";
                    } else {
                        echo "<a href='" . $createLink('u', $user) . "'>$user</a> | ";
                    }
                }

                if (!empty($username) && $username !== $user) {
                    echo "<b>$username</b> | ";
                }

                if (!empty($username)) {
                    echo "<a href='" . $createLink('u', null) . "'>Clear Filter</a>";
                } else {
                    echo "<b>Clear Filter</b>";
                }
                echo "</p>";
            }

            // Game filter
            if (!empty($gameID)) {
                echo "<p><b>Game</b>";
                echo ": <b>$gameTitle ($consoleName)</b>";
                echo " | <a href='" . $createLink('g', null) . "'>Clear Filter</a></p>";
            }
            echo "<br style='clear:both'>";

            echo "<div class='table-wrapper'><table><tbody>";

            // Sortable table headers
            echo $linkSorting(ClaimSorting::toString(ClaimSorting::UserDescending), ClaimSorting::UserDescending, ClaimSorting::UserAscending);
            echo $linkSorting(ClaimSorting::toString(ClaimSorting::GameDescending), ClaimSorting::GameDescending, ClaimSorting::GameAscending);
            echo $linkSorting(ClaimSorting::toString(ClaimSorting::ClaimTypeDescending), ClaimSorting::ClaimTypeDescending, ClaimSorting::ClaimTypeAscending);
            echo $linkSorting(ClaimSorting::toString(ClaimSorting::SetTypeDescending), ClaimSorting::SetTypeDescending, ClaimSorting::SetTypeAscending);
            echo $linkSorting(ClaimSorting::toString(ClaimSorting::ClaimStatusDescending), ClaimSorting::ClaimStatusDescending, ClaimSorting::ClaimStatusAscending);
            echo $linkSorting(ClaimSorting::toString(ClaimSorting::SpecialDescending), ClaimSorting::SpecialDescending, ClaimSorting::SpecialAscending);
            echo $linkSorting(ClaimSorting::toString(ClaimSorting::ClaimDateDescending), ClaimSorting::ClaimDateDescending, ClaimSorting::ClaimDateAscending);
            echo $linkSorting(ClaimSorting::toString(ClaimSorting::FinishedDateDescending, $claimFilters), ClaimSorting::FinishedDateDescending, ClaimSorting::FinishedDateDescending);

            // Loop through the claims and display them in the table
            foreach ($claimData as $claim) {
                $claimUser = $claim['User'];
                echo "<tr><td class='text-nowrap'>";
                echo GetUserAndTooltipDiv($claimUser, true);
                echo "</td>";
                echo "<td class='text-nowrap'><div class='fixheightcell'>";
                echo GetUserAndTooltipDiv($claimUser, false);
                echo "</div></td>";
                echo "<td>";
                echo GetGameAndTooltipDiv($claim['GameID'], $claim['GameTitle'], $claim['GameIcon'], $claim['ConsoleName']);
                echo "</td>";
                echo "<td>" . ($claim['ClaimType'] == ClaimType::Primary ? ClaimType::toString(ClaimType::Primary) : ClaimType::toString(ClaimType::Collaboration)) . "</td>";
                echo "<td>" . ($claim['SetType'] == ClaimSetType::NewSet ? ClaimSetType::toString(ClaimSetType::NewSet) : ClaimSetType::toString(ClaimSetType::Revision)) . "</td>";
                echo "<td>";
                switch ($claim['Status']) {
                    case ClaimStatus::Active:
                        echo ClaimStatus::toString(ClaimStatus::Active);
                        break;
                    case ClaimStatus::Complete:
                        echo ClaimStatus::toString(ClaimStatus::Complete);
                        break;
                    case ClaimStatus::Dropped:
                        echo ClaimStatus::toString(ClaimStatus::Dropped);
                        break;
                    default:
                        echo ClaimStatus::toString(ClaimStatus::Active);
                        break;
                }
                echo "</td>";
                echo "<td>" . ClaimSpecial::toString($claim['Special']) . "</td>";
                echo "<td>" . getNiceDate(strtotime($claim['Created'])) . "</td>";
                echo "<td>" . getNiceDate(strtotime($claim['DoneTime'])) . "</td></tr>";
            }
            echo "</tbody></table></div>";

            echo "<div class='rightalign row'>";
            $baseLink = $createLink(null, null);
            $baseLink .= (str_contains($baseLink, '?') ? '&' : '?');
            RenderPaginator($totalClaims, $maxCount, $offset, "${baseLink}o=");
            echo "</div>";
        } else { // Show expiring claims
            echo "<h3>Expiring Claims</h3>";

            // Add username filter section if the user is in the list
            $expiringClaims = getExpiringClaim($user);
            $expired = (int) $expiringClaims["Expired"];
            $expiring = (int) $expiringClaims["Expiring"];
            if ((isset($user) || !empty($username)) && ($expired + $expiring) > 0) {
                echo "<p><b>User:</b> ";
                if (isset($user)) {
                    if ($username == $user) {
                        echo "<b>$user</b> | ";
                    } else {
                        echo "<a href='/claimlist.php?t=1&u=$user'>$user</a> | ";
                    }
                }

                if (!empty($username) && $username !== $user) {
                    echo "<b>$username</b> | ";
                }

                if (!empty($username)) {
                    echo "<a href='/claimlist.php?t=1'>Clear Filter</a>";
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
                echo "<tr><td class='text-nowrap'>";
                echo GetUserAndTooltipDiv($claimUser, true);
                echo "</td>";
                echo "<td class='text-nowrap'><div class='fixheightcell'>";
                echo GetUserAndTooltipDiv($claimUser, false);
                echo "</div></td>";

                echo "<td>";
                echo GetGameAndTooltipDiv($claim['GameID'], $claim['GameTitle'], $claim['GameIcon'], $claim['ConsoleName']);
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
        }
        ?>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
