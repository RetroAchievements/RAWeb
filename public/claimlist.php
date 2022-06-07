<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

authenticateFromCookie($user, $permissions, $userDetails);

$defaultFilter = 415; // Show all active claims
$defaultSorting = 8; // Sort by claim date
$maxCount = 50;
$offset = 0;
$totalClaims = 0;

$errorCode = requestInputSanitized('e');
$type = requestInputSanitized('t', 0, 'integer');
$username = requestInputSanitized('u', null);
$claimFilter = requestInputSanitized('f', $defaultFilter, 'integer');
$sortType = requestInputSanitized('s', $defaultSorting, 'integer');
$gameID = requestInputSanitized('g', null, 'integer');
$limit = requestInputSanitized('c', $maxCount, 'integer');
$offset = requestInputSanitized('o', $offset, 'integer');

if ($type == 0) { // Get general data
    $claimData = getFilteredClaimData($gameID, $claimFilter, $sortType, false, $username, false, $offset, $limit);
    $totalClaims = getFilteredClaimData($gameID, $claimFilter, $sortType, false, $username, true);
} else { // Get expiring data
    $claimData = getFilteredClaimData(0, $defaultFilter, 19, true, $username, false); // Active sorted by expiring
}
$activeClaimCount = getActiveClaimCount();

// Filter flags
$primaryClaim = ($claimFilter & (1 << 0));
$collaborationClaim = ($claimFilter & (1 << 1));
$newSetClaim = ($claimFilter & (1 << 2));
$revisionClaim = ($claimFilter & (1 << 3));
$activeClaim = ($claimFilter & (1 << 4));
$completeClaim = ($claimFilter & (1 << 5));
$droppedClaim = ($claimFilter & (1 << 6));
$developerClaim = ($claimFilter & (1 << 7));
$juniorDeveloperClaim = ($claimFilter & (1 << 8));

// Sorting flags
$sortUser = ($sortType == 2) ? 12 : 2;           // User
$sortGame = ($sortType == 3) ? 13 : 3;           // Game
$sortClaimType = ($sortType == 4) ? 14 : 4;      // Claim Type
$sortSetType = ($sortType == 5) ? 15 : 5;        // Set Type
$sortClaimStatus = ($sortType == 6) ? 16 : 6;    // Claim Status
$sortSpecial = ($sortType == 7) ? 17 : 7;        // Special
$sortClaimDate = ($sortType == 8) ? 18 : 8;      // Claim Date
$sortExpirationDate = ($sortType == 9) ? 19 : 9; // Expiration Date

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

            // Claim Type filter
            echo "<div class='embedded mb-1'>";
            echo "<div>";
            echo "<b>Claim Type:</b> ";
            if ($primaryClaim) {
                echo "<b><a href='/claimlist.php?s=$sortType&f=" . ($claimFilter & ~(1 << 0)) . "&u=$username&g=$gameID'>*Primary</a></b> | ";
            } else {
                echo "<a href='/claimlist.php?s=$sortType&f=" . ($claimFilter | (1 << 0)) . "&u=$username&g=$gameID'>Primary</a> | ";
            }

            if ($collaborationClaim) {
                echo "<b><a href='/claimlist.php?s=$sortType&f=" . ($claimFilter & ~(1 << 1)) . "&u=$username&g=$gameID'>*Collaboration</a></b>";
            } else {
                echo "<a href='/claimlist.php?s=$sortType&f=" . ($claimFilter | (1 << 1)) . "&u=$username&g=$gameID'>Collaboration</a>";
            }
            echo "</div>";

            // Set Type filter
            echo "<div>";
            echo "<b>Set Type:</b> ";
            if ($newSetClaim) {
                echo "<b><a href='/claimlist.php?s=$sortType&f=" . ($claimFilter & ~(1 << 2)) . "&u=$username&g=$gameID'>*New Set</a></b> | ";
            } else {
                echo "<a href='/claimlist.php?s=$sortType&f=" . ($claimFilter | (1 << 2)) . "&u=$username&g=$gameID'>New Set</a> | ";
            }

            if ($revisionClaim) {
                echo "<b><a href='/claimlist.php?s=$sortType&f=" . ($claimFilter & ~(1 << 3)) . "&u=$username&g=$gameID'>*Revision</a></b>";
            } else {
                echo "<a href='/claimlist.php?s=$sortType&f=" . ($claimFilter | (1 << 3)) . "&u=$username&g=$gameID'>Revision</a>";
            }
            echo "</div>";

            // Claim Status filter
            echo "<div>";
            echo "<b>Claim Status:</b> ";
            if ($activeClaim) {
                echo "<b><a href='/claimlist.php?s=$sortType&f=" . ($claimFilter & ~(1 << 4)) . "&u=$username&g=$gameID'>*Active</a></b> | ";
            } else {
                echo "<a href='/claimlist.php?s=$sortType&f=" . ($claimFilter | (1 << 4)) . "&u=$username&g=$gameID'>Active</a> | ";
            }

            if ($completeClaim) {
                echo "<b><a href='/claimlist.php?s=$sortType&f=" . ($claimFilter & ~(1 << 5)) . "&u=$username&g=$gameID'>*Complete</a></b> | ";
            } else {
                echo "<a href='/claimlist.php?s=$sortType&f=" . ($claimFilter | (1 << 5)) . "&u=$username&g=$gameID'>Complete</a> | ";
            }

            if ($droppedClaim) {
                echo "<b><a href='/claimlist.php?s=$sortType&f=" . ($claimFilter & ~(1 << 6)) . "&u=$username&g=$gameID'>*Dropped</a></b>";
            } else {
                echo "<a href='/claimlist.php?s=$sortType&f=" . ($claimFilter | (1 << 6)) . "&u=$username&g=$gameID'>Dropped</a>";
            }
            echo "</div>";

            // Developer Status filter
            echo "<div>";
            echo "<b>Developer Status:</b> ";
            if ($developerClaim) {
                echo "<b><a href='/claimlist.php?s=$sortType&f=" . ($claimFilter & ~(1 << 7)) . "&u=$username&g=$gameID'>*Developer</a></b> | ";
            } else {
                echo "<a href='/claimlist.php?s=$sortType&f=" . ($claimFilter | (1 << 7)) . "&u=$username&g=$gameID'>Developer</a> | ";
            }

            if ($juniorDeveloperClaim) {
                echo "<b><a href='/claimlist.php?s=$sortType&f=" . ($claimFilter & ~(1 << 8)) . "&u=$username&g=$gameID'>*Junior Developer</a></b>";
            } else {
                echo "<a href='/claimlist.php?s=$sortType&f=" . ($claimFilter | (1 << 8)) . "&u=$username&g=$gameID'>Junior Developer</a>";
            }
            echo "</div>";

            // Clear Filter
            if ($claimFilter != $defaultFilter) {
                echo "<div>";
                echo "<a href='/claimlist.php?s=$sortType&f=" . $defaultFilter . "&u=$username&g=$gameID'>Clear Filter</a>";
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
                        echo "<a href='/claimlist.php?s=$sortType&f=$claimFilter&u=$user&g=$gameID'>$user</a> | ";
                    }
                }

                if (!empty($username) && $username !== $user) {
                    echo "<b>$username</b> | ";
                }

                if (!empty($username)) {
                    echo "<a href='/claimlist.php?s=$sortType&f=$claimFilter&g=$gameID'>Clear Filter</a>";
                } else {
                    echo "<b>Clear Filter</b>";
                }
                echo "</p>";
            }

            // Game filter
            if (!empty($gameID)) {
                echo "<p><b>Game</b>";
                echo ": <b>$gameTitle ($consoleName)</b>";
                echo " | <a href='/claimlist.php?s=$sortType&f=$claimFilter&u=$username'>Clear Filter</a></p>";
            }
            echo "<br style='clear:both'>";

            echo "<div class='table-wrapper'><table><tbody>";

            // Sortable User header
            if (($sortType % 10) == 2) {
                if ($sortUser == 2) {
                    echo "<th colspan='2'><b><a href='/claimlist.php?s=$sortUser&f=$claimFilter&u=$username&g=$gameID'>User &#9650;</a></b></th>";
                } else {
                    echo "<th colspan='2'><b><a href='/claimlist.php?s=$sortUser&f=$claimFilter&u=$username&g=$gameID'>User &#9660;</a></b></th>";
                }
            } else {
                echo "<th colspan='2'><a href='/claimlist.php?s=$sortUser&f=$claimFilter&u=$username&g=$gameID'>User</a></th>";
            }

            // Sortable Game header
            if (($sortType % 10) == 3) {
                if ($sortGame == 3) {
                    echo "<th><b><a href='/claimlist.php?s=$sortGame&f=$claimFilter&u=$username&g=$gameID'>Game &#9650;</a></b></th>";
                } else {
                    echo "<th><b><a href='/claimlist.php?s=$sortGame&f=$claimFilter&u=$username&g=$gameID'>Game &#9660;</a></b></th>";
                }
            } else {
                echo "<th><a href='/claimlist.php?s=$sortGame&f=$claimFilter&u=$username&g=$gameID'>Game</a></th>";
            }

            // Sortable Claim Type header
            if (($sortType % 10) == 4) {
                if ($sortClaimType == 4) {
                    echo "<th><b><a href='/claimlist.php?s=$sortClaimType&f=$claimFilter&u=$username&g=$gameID'>Claim Type &#9650;</a></b></th>";
                } else {
                    echo "<th><b><a href='/claimlist.php?s=$sortClaimType&f=$claimFilter&u=$username&g=$gameID'>Claim Type &#9660;</a></b></th>";
                }
            } else {
                echo "<th><a href='/claimlist.php?s=$sortClaimType&f=$claimFilter&u=$username&g=$gameID'>Claim Type</a></th>";
            }

            // Sortable Set Type header
            if (($sortType % 10) == 5) {
                if ($sortSetType == 5) {
                    echo "<th><b><a href='/claimlist.php?s=$sortSetType&f=$claimFilter&u=$username&g=$gameID'>Set Type &#9650;</a></b></th>";
                } else {
                    echo "<th><b><a href='/claimlist.php?s=$sortSetType&f=$claimFilter&u=$username&g=$gameID'>Set Type &#9660;</a></b></th>";
                }
            } else {
                echo "<th><a href='/claimlist.php?s=$sortSetType&f=$claimFilter&u=$username&g=$gameID'>Set Type</a></th>";
            }

            // Sortable Claim Status header
            if (($sortType % 10) == 6) {
                if ($sortClaimStatus == 6) {
                    echo "<th><b><a href='/claimlist.php?s=$sortClaimStatus&f=$claimFilter&u=$username&g=$gameID'>Claim Status &#9650;</a></b></th>";
                } else {
                    echo "<th><b><a href='/claimlist.php?s=$sortClaimStatus&f=$claimFilter&u=$username&g=$gameID'>Claim Status &#9660;</a></b></th>";
                }
            } else {
                echo "<th><a href='/claimlist.php?s=$sortClaimStatus&f=$claimFilter&u=$username&g=$gameID'>Claim Status</a></th>";
            }

            // Sortable Special header
            $specialTooltip = "0: Standard Claim\n1: Own Revision Claim\n2: Free Rollout Claim\n3: Approved for Future Release";
            if (($sortType % 10) == 7) {
                if ($sortSpecial == 7) {
                    echo "<th><b><a href='/claimlist.php?s=$sortSpecial&f=$claimFilter&u=$username&g=$gameID' title='$specialTooltip'>Special &#9650;</a></b></th>";
                } else {
                    echo "<th><b><a href='/claimlist.php?s=$sortSpecial&f=$claimFilter&u=$username&g=$gameID' title='$specialTooltip'>Special &#9660;</a></b></th>";
                }
            } else {
                echo "<th><a href='/claimlist.php?s=$sortSpecial&f=$claimFilter&u=$username&g=$gameID' title='$specialTooltip'>Special</a></th>";
            }

            // Sortable Expiration Date header
            if (($sortType % 10) == 8) {
                if ($sortClaimDate == 8) {
                    echo "<th><b><a href='/claimlist.php?s=$sortClaimDate&f=$claimFilter&u=$username&g=$gameID'>Claim Date &#9650;</a></b></th>";
                } else {
                    echo "<th><b><a href='/claimlist.php?s=$sortClaimDate&f=$claimFilter&u=$username&g=$gameID'>Claim Date &#9660;</a></b></th>";
                }
            } else {
                echo "<th><a href='/claimlist.php?s=$sortClaimDate&f=$claimFilter&u=$username&g=$gameID'>Claim Date</a></th>";
            }

            // Create the finished date text depending on which status' are filtered in
            $dateText = 'Expiration / Completion / Drop';
            if ($activeClaim && $completeClaim && !$droppedClaim) {
                $dateText = 'Expiration / Completion';
            } elseif ($activeClaim && !$completeClaim && $droppedClaim) {
                $dateText = 'Expiration / Drop';
            } elseif ($activeClaim && !$completeClaim && !$droppedClaim) {
                $dateText = 'Expiration';
            } elseif (!$activeClaim && $completeClaim && $droppedClaim) {
                $dateText = 'Completion / Drop';
            } elseif (!$activeClaim && $completeClaim && !$droppedClaim) {
                $dateText = 'Completion';
            } elseif (!$activeClaim && !$completeClaim && $droppedClaim) {
                $dateText = 'Drop';
            }
            $dateText .= ' Date';

            // Sortable Expiration Date header
            if (($sortType % 10) == 9) {
                if ($sortExpirationDate == 9) {
                    echo "<th><b><a href='/claimlist.php?s=$sortExpirationDate&f=$claimFilter&u=$username&g=$gameID'>$dateText &#9650;</a></b></th>";
                } else {
                    echo "<th><b><a href='/claimlist.php?s=$sortExpirationDate&f=$claimFilter&u=$username&g=$gameID'>$dateText &#9660;</a></b></th>";
                }
            } else {
                echo "<th><a href='/claimlist.php?s=$sortExpirationDate&f=$claimFilter&u=$username&g=$gameID'>$dateText</a></th>";
            }

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
                echo "<td>" . ($claim['ClaimType'] == 0 ? "Primary" : "Collaboration") . "</td>";
                echo "<td>" . ($claim['SetType'] == 0 ? "New" : "Revision") . "</td>";
                echo "<td>";
                switch ($claim['Status']) {
                    case 0:
                        echo "Active";
                        break;
                    case 1:
                        echo "Complete";
                        break;
                    case 2:
                        echo "Dropped";
                        break;
                    default:
                        echo "Active";
                        break;
                }
                echo "</td>";
                echo "<td>" . $claim['Special'] . "</td>";
                echo "<td>" . getNiceDate(strtotime($claim['Created'])) . "</td>";
                echo "<td>" . getNiceDate(strtotime($claim['DoneTime'])) . "</td></tr>";
            }
            echo "</tbody></table></div>";

            // Add page traversal links
            if ($maxCount != 0 && ((int) $totalClaims) > $maxCount) {
                echo "\n<br/><div class='rightalign row'>";
                RenderPaginator($totalClaims, $maxCount, $offset, "/claimlist.php?s=$sortType&f=$claimFilter&u=$username&g=$gameID&o=");
                echo "</div>";
            }
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
            echo "<th colspan='2'>User</th>";
            echo "<th>Game</th>";
            echo "<th>Claim Type</th>";
            echo "<th>Set Type</th>";
            echo "<th>Special</th>";
            echo "<th>Claim Date</th>";
            echo "<th><b>Expiration Date &#9660;</b></th>";
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
                echo "<td>" . ($claim['ClaimType'] == 0 ? "Primary" : "Collaboration") . "</td>";
                echo "<td>" . ($claim['SetType'] == 0 ? "New" : "Revision") . "</td>";
                echo "<td>" . $claim['Special'] . "</td>";
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
