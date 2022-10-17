<?php

use RA\ClaimFilters;
use RA\ClaimSetType;
use RA\ClaimSorting;
use RA\ClaimSpecial;
use RA\ClaimStatus;
use RA\ClaimType;
use RA\Permissions;

authenticateFromCookie($user, $permissions, $userDetails);

$defaultFilter = ClaimFilters::Default; // Show all active claims
$defaultSorting = ClaimSorting::ClaimDateDescending; // Sort by claim date
$maxCount = 50;
$offset = 0;
$totalClaims = 0;

$username = requestInputSanitized('u', null);
$claimFilters = requestInputSanitized('f', $defaultFilter, 'integer');
$sortType = requestInputSanitized('s', $defaultSorting, 'integer');
$gameID = requestInputSanitized('g', null, 'integer');
$limit = requestInputSanitized('c', $maxCount, 'integer');
$offset = requestInputSanitized('o', $offset, 'integer');

$claimData = getFilteredClaimData($gameID, $claimFilters, $sortType, false, $username, false, $offset, $limit);
$totalClaims = getFilteredClaimData($gameID, $claimFilters, $sortType, false, $username, true);

$activeClaimCount = getActiveClaimCount();

if (!empty($gameID)) {
    getGameTitleFromID($gameID, $gameTitle, $consoleID, $consoleName, $forumTopic, $gameData);
}

sanitize_outputs(
    $gameTitle,
    $consoleName,
);

RenderContentStart("Claim List");
?>
<div id='mainpage'>
    <div id='fullcontainer'>
        <?php
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
            if (($sortType % 10) == $sort1) { // if on the current sort header
                if ($sortType == $sort2) {
                    return "<th><b><a href='" . $createLink('s', $sort1) . "'>$label &#9650;</a></b></th>"; // Ascending
                } else {
                    return "<th><b><a href='" . $createLink('s', $sort2) . "'>$label &#9660;</a></b></th>"; // Descending
                }
            } else {
                return "<th><a href='" . $createLink('s', $sort1) . "'>$label</a></th>";
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
            echo "<p class='embedded'><b>User:</b> ";
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
            echo "<p class='embedded'><b>Game</b>";
            echo ": <b>$gameTitle ($consoleName)</b>";
            echo " | <a href='" . $createLink('g', null) . "'>Clear Filter</a></p>";
        }
        echo "<br style='clear:both'>";

        echo "<div class='table-wrapper'><table><tbody>";
        echo "<th></th>";
        // Sortable table headers
        echo $linkSorting(ClaimSorting::toString(ClaimSorting::UserDescending), ClaimSorting::UserDescending, ClaimSorting::UserAscending);
        echo $linkSorting(ClaimSorting::toString(ClaimSorting::GameDescending), ClaimSorting::GameDescending, ClaimSorting::GameAscending);
        echo $linkSorting(ClaimSorting::toString(ClaimSorting::ClaimTypeDescending), ClaimSorting::ClaimTypeDescending, ClaimSorting::ClaimTypeAscending);
        echo $linkSorting(ClaimSorting::toString(ClaimSorting::SetTypeDescending), ClaimSorting::SetTypeDescending, ClaimSorting::SetTypeAscending);
        echo $linkSorting(ClaimSorting::toString(ClaimSorting::ClaimStatusDescending), ClaimSorting::ClaimStatusDescending, ClaimSorting::ClaimStatusAscending);
        echo $linkSorting(ClaimSorting::toString(ClaimSorting::SpecialDescending), ClaimSorting::SpecialDescending, ClaimSorting::SpecialAscending);
        echo $linkSorting(ClaimSorting::toString(ClaimSorting::ClaimDateDescending), ClaimSorting::ClaimDateDescending, ClaimSorting::ClaimDateAscending);
        echo $linkSorting(ClaimSorting::toString(ClaimSorting::FinishedDateDescending, $claimFilters), ClaimSorting::FinishedDateDescending, ClaimSorting::FinishedDateAscending);

        // Loop through the claims and display them in the table
        foreach ($claimData as $claim) {
            $claimUser = $claim['User'];
            echo "<tr>";
            echo "<td>";
            echo userAvatar($claimUser, label: false);
            echo "</td>";
            echo "<td class='whitespace-nowrap'>";
            echo userAvatar($claimUser, icon: false);
            echo "</td>";
            echo "<td>";
            echo gameAvatar($claim);
            echo "</td>";
            echo "<td>" . ($claim['ClaimType'] == ClaimType::Primary ? ClaimType::toString(ClaimType::Primary) : ClaimType::toString(ClaimType::Collaboration)) . "</td>";
            echo "<td>" . ($claim['SetType'] == ClaimSetType::NewSet ? ClaimSetType::toString(ClaimSetType::NewSet) : ClaimSetType::toString(ClaimSetType::Revision)) . "</td>";
            echo "<td>" . ClaimStatus::toString($claim['Status']) . "</td>";
            echo "<td>" . ClaimSpecial::toString($claim['Special']) . "</td>";
            echo "<td>" . getNiceDate(strtotime($claim['Created'])) . "</td>";
            echo "<td>" . getNiceDate(strtotime($claim['DoneTime'])) . "</td></tr>";
        }
        echo "</tbody></table></div>";

        echo "<div class='float-right row'>";
        $baseLink = $createLink(null, null);
        $baseLink .= (str_contains($baseLink, '?') ? '&' : '?');
        if ($totalClaims) {
            RenderPaginator($totalClaims, $maxCount, $offset, "{$baseLink}o=");
        }
        echo "</div>";
        ?>
    </div>
</div>
<?php RenderContentEnd(); ?>
