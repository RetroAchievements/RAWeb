<?php

use App\Community\Enums\ClaimFilters;
use App\Community\Enums\ClaimSetType;
use App\Community\Enums\ClaimSorting;
use App\Community\Enums\ClaimSpecial;
use App\Community\Enums\ClaimStatus;
use App\Community\Enums\ClaimType;
use App\Site\Enums\Permissions;
use Illuminate\Support\Str;

authenticateFromCookie($user, $permissions, $userDetails);

$defaultFilter = ClaimFilters::AllActiveClaims;
$defaultSorting = ClaimSorting::ClaimDateDescending;
$maxCount = 50;
$offset = 0;
$totalClaims = 0;

$username = requestInputSanitized('u', null);
$claimFilters = requestInputSanitized('f', $defaultFilter, 'integer');
$sortType = requestInputSanitized('s', $defaultSorting, 'integer');
$gameID = requestInputSanitized('g', null, 'integer');
$limit = requestInputSanitized('c', $maxCount, 'integer');
$offset = requestInputSanitized('o', $offset, 'integer');

$claimData = getFilteredClaims($gameID, $claimFilters, $sortType, false, $username, $offset, $limit);
$totalClaims = getFilteredClaims($gameID, $claimFilters, $sortType, false, $username)->count();

$activeClaimCount = getActiveClaimCount();

if (!empty($gameID)) {
    $gameData = getGameData($gameID);
    $gameTitle = $gameData['Title'] ?? '';
    $consoleName = $gameData['ConsoleName'] ?? '';
    sanitize_outputs(
        $gameTitle,
        $consoleName,
    );
}

RenderContentStart("Claim List");
?>
<article>
    <?php
    echo "<h3>Claim List - $activeClaimCount Active Claims</h3>";
    echo "<h4>Filters - $totalClaims Claims Filtered</h4>";
    echo "<div class='embedded mb-1'>";

    $createLink = function ($flag, $value, $flag2 = null, $value2 = null) use ($sortType, $claimFilters, $username, $gameID) {
        $appendParam = function (&$link, $param, $fallback, $default) use ($flag, $value, $flag2, $value2) {
            $param_value = ($flag == $param) ? $value : (($flag2 == $param) ? $value2 : $fallback);
            if ($param_value != $default) {
                $link .= Str::contains($link, '?') ? '&' : '?';
                $link .= $param . '=' . $param_value;
            }
        };

        $link = "/claimlist.php";
        $appendParam($link, 's', $sortType, 8);
        $appendParam($link, 'f', $claimFilters, ClaimFilters::AllActiveClaims);
        $appendParam($link, 'u', $username, null);
        $appendParam($link, 'g', $gameID, null);

        return $link;
    };

    $linkFilter = function (string $label, int $claimFilter) use ($claimFilters, $createLink) {
        if ($claimFilters & $claimFilter) {
            return "<b><a href='" . $createLink('f', $claimFilters & ~$claimFilter) . "'>*$label</a></b>";
        }

        return "<a href='" . $createLink('f', $claimFilters | $claimFilter) . "'>$label</a>";
    };

    $linkSorting = function (string $label, int $sort1, int $sort2) use ($sortType, $createLink) {
        $textAlign = ($label != 'Game' and $label != 'Dev') ? 'text-center' : 'text-left';
        $th = "<th class='text-xs $textAlign whitespace-nowrap'>";
        if (($sortType % 10) == $sort1) { // if on the current sort header
            if ($sortType == $sort2) {
                return "$th<b><a href='" . $createLink('s', $sort1) . "'>$label &#9650;</a></b></th>"; // Ascending
            } else {
                return "$th<b><a href='" . $createLink('s', $sort2) . "'>$label &#9660;</a></b></th>"; // Descending
            }
        } else {
            return "$th<a href='" . $createLink('s', $sort1) . "'>$label</a></th>";
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
    echo "<b>Status:</b> ";
    echo $linkFilter(ClaimStatus::toString(ClaimStatus::Active), ClaimFilters::ActiveClaim) . ' | ';
    echo $linkFilter(ClaimStatus::toString(ClaimStatus::InReview), ClaimFilters::InReviewClaim) . ' | ';
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

    echo "<div class='table-wrapper'><table class='table-highlight text-xs'><tbody>";

    // Sortable table headers
    echo "<tr class='do-not-highlight'>";
    echo $linkSorting(ClaimSorting::toString(ClaimSorting::GameDescending), ClaimSorting::GameDescending, ClaimSorting::GameAscending);
    echo "<th></th>";
    echo $linkSorting(ClaimSorting::toString(ClaimSorting::UserDescending), ClaimSorting::UserDescending, ClaimSorting::UserAscending);
    echo "<th></th>";
    echo $linkSorting(ClaimSorting::toString(ClaimSorting::ClaimTypeDescending), ClaimSorting::ClaimTypeDescending, ClaimSorting::ClaimTypeAscending);
    echo $linkSorting(ClaimSorting::toString(ClaimSorting::SetTypeDescending), ClaimSorting::SetTypeDescending, ClaimSorting::SetTypeAscending);
    echo $linkSorting(ClaimSorting::toString(ClaimSorting::ClaimStatusDescending), ClaimSorting::ClaimStatusDescending, ClaimSorting::ClaimStatusAscending);
    echo $linkSorting(ClaimSorting::toString(ClaimSorting::SpecialDescending), ClaimSorting::SpecialDescending, ClaimSorting::SpecialAscending);
    echo $linkSorting(ClaimSorting::toString(ClaimSorting::ClaimDateDescending), ClaimSorting::ClaimDateDescending, ClaimSorting::ClaimDateAscending);
    echo $linkSorting(ClaimSorting::toString(ClaimSorting::FinishedDateDescending, $claimFilters), ClaimSorting::FinishedDateDescending, ClaimSorting::FinishedDateAscending);
    echo "</tr>";

    // Loop through the claims and display them in the table
    foreach ($claimData as $claim) {
        $claimUser = $claim['User'];
        $fieldSpecial = ClaimSpecial::toString($claim['Special']);
        if ($fieldSpecial === 'None') {
            $fieldSpecial = '--';
        }

        echo "<tr>";
        echo "<td>";
        echo gameAvatar($claim, label: false);
        echo "</td>";
        echo "<td width='300'>";
        echo gameAvatar($claim, icon: false);
        echo "</td>";
        echo "<td>";
        echo userAvatar($claimUser, label: false);
        echo "</td>";
        echo "<td>";
        echo userAvatar($claimUser, icon: false);
        echo "</td>";
        echo "<td class='text-center'>" . ($claim['ClaimType'] == ClaimType::Primary ? ClaimType::toString(ClaimType::Primary) : ClaimType::toString(ClaimType::Collaboration)) . "</td>";
        echo "<td class='text-center'>" . ($claim['SetType'] == ClaimSetType::NewSet ? ClaimSetType::toString(ClaimSetType::NewSet) : ClaimSetType::toString(ClaimSetType::Revision)) . "</td>";
        echo "<td class='text-center'>" . ClaimStatus::toString($claim['Status']) . "</td>";
        echo "<td class='text-center'>" . $fieldSpecial . "</td>";
        echo "<td class='smalldate text-center whitespace-nowrap'>" . getNiceDate(strtotime($claim['Created'])) . "</td>";
        echo "<td class='smalldate text-center whitespace-nowrap'>" . getNiceDate(strtotime($claim['DoneTime'])) . "</td></tr>";
    }
    echo "</tbody></table></div>";

    echo "<div class='text-right'>";
    $baseLink = $createLink(null, null);
    $baseLink .= (Str::contains($baseLink, '?') ? '&' : '?');
    if ($totalClaims) {
        RenderPaginator($totalClaims, $maxCount, $offset, "{$baseLink}o=");
    }
    echo "</div>";
    ?>
</article>
<?php RenderContentEnd(); ?>
