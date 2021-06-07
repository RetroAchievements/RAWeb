<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

if (!RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions)) {
    header("Location: " . getenv('APP_URL'));
    exit;
}

$maxCount = 100;
$count = requestInputSanitized('c', $maxCount, 'integer');
$offset = requestInputSanitized('o', 0, 'integer');

$ticketID = requestInputSanitized('i', 0, 'integer');
$defaultFilter = 2041; //2041 sets all filters active except for Closed and Resolved
$ticketFilters = requestInputSanitized('t', $defaultFilter, 'integer');

$reportStates = ["Closed", "Open", "Resolved"];

$altTicketData = null;
$commentData = null;
$filteredTicketsCount = null;
$numArticleComments = null;
$numClosedTickets = null;
$numOpenTickets = null;
$ticketData = null;
if ($ticketID != 0) {
    $ticketData = getTicket($ticketID);
    if ($ticketData == false) {
        $ticketID = 0;
        $errorCode = 'notfound';
    }

    $action = requestInputSanitized('action', null);
    $reason = null;
    $ticketState = 1;
    switch ($action) {
        case "closed-mistaken":
            $ticketState = 0;
            $reason = "Mistaken report";
            break;

        case "resolved":
            if ($permissions >= \RA\Permissions::Developer) {
                $ticketState = 2;
            }
            break;

        case "demoted":
            if ($permissions >= \RA\Permissions::Developer) {
                $ticketState = 0;
                $reason = "Demoted";
            }
            break;

        case "not-enough-info":
            if ($permissions >= \RA\Permissions::Developer) {
                $ticketState = 0;
                $reason = "Not enough information";
            }
            break;

        case "wrong-rom":
            if ($permissions >= \RA\Permissions::Developer) {
                $ticketState = 0;
                $reason = "Wrong ROM";
            }
            break;

        case "network":
            if ($permissions >= \RA\Permissions::Developer) {
                $ticketState = 0;
                $reason = "Network problems";
            }
            break;

        case "unable-to-reproduce":
            if ($permissions >= \RA\Permissions::Developer) {
                $ticketState = 0;
                $reason = "Unable to reproduce";
            }
            break;

        case "closed-other":
            if ($permissions >= \RA\Permissions::Developer) {
                $ticketState = 0;
                $reason = "See the comments";
            }
            break;

        case "reopen":
            $ticketState = 1;
            break;

        default:
            $action = null;
            break;
    }

    if ($action != null &&
        $ticketState != $ticketData['ReportState'] &&
        (
            $permissions >= \RA\Permissions::Developer ||
            $user == $ticketData['ReportedBy']
        )
    ) {
        updateTicket($user, $ticketID, $ticketState, $reason);
        $ticketData = getTicket($ticketID);
    }

    $numArticleComments = getArticleComments(7, $ticketID, 0, 20, $commentData);

    $altTicketData = getAllTickets(0, 99, null, null, $ticketData['AchievementID'], 2047); //2047 sets all filters enabled so we get closed/resolved tickets as well
    //var_dump($altTicketData);
    $numOpenTickets = 0;
    foreach ($altTicketData as $pastTicket) {
        settype($pastTicket["ID"], 'integer');

        if ($pastTicket["ReportState"] == 1 && $pastTicket["ID"] !== $ticketID) {
            $numOpenTickets++;
        }
    }

    $numClosedTickets = (count($altTicketData) - $numOpenTickets) - 1;
}

$assignedToUser = null;
$gamesTableFlag = 0;
$gameIDGiven = 0;
if ($ticketID == 0) {
    $gamesTableFlag = requestInputSanitized('f', null, 'integer');
    if ($gamesTableFlag == 1) {
        $count = requestInputSanitized('c', 100, 'integer');
        $ticketData = gamesSortedByOpenTickets($count);
    } else {
        $assignedToUser = requestInputSanitized('u', null);
        if (!isValidUsername($assignedToUser)) {
            $assignedToUser = null;
        }
        $gameIDGiven = requestInputSanitized('g', null, 'integer');

        $achievementIDGiven = requestInputSanitized('a', null, 'integer');
        if ($achievementIDGiven > 0) {
            $achievementData = GetAchievementData($achievementIDGiven);
            $achievementTitle = $achievementData['Title'];
            $gameIDGiven = $achievementData['GameID']; // overwrite the given game ID
        }

        if ($gamesTableFlag == 5) {
            $ticketData = getAllTickets($offset, $count, $assignedToUser, $gameIDGiven, $achievementIDGiven, $ticketFilters, true);
        } else {
            $ticketData = getAllTickets($offset, $count, $assignedToUser, $gameIDGiven, $achievementIDGiven, $ticketFilters);
        }
    }
}

if (!empty($gameIDGiven)) {
    getGameTitleFromID($gameIDGiven, $gameTitle, $consoleID, $consoleName, $forumTopic, $gameData);
}

sanitize_outputs(
    $achievementTitle,
    $gameTitle,
    $consoleName,
);

$pageTitle = "Ticket Manager";

$errorCode = requestInputSanitized('e');
RenderHtmlStart();
RenderHtmlHead($pageTitle);
?>
<body>
<?php RenderTitleBar($user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions); ?>
<?php RenderToolbar($user, $permissions); ?>
<div id="mainpage">
    <?php RenderErrorCodeWarning($errorCode); ?>
    <div id="fullcontainer">
        <?php
        echo "<div class='navpath'>";
        if ($gamesTableFlag == 1) {
            echo "<a href='/ticketmanager.php'>$pageTitle</a></b> &raquo; <b>Games With Open Tickets";
        } else {
            if ($ticketID == 0) {
                echo "<a href='/ticketmanager.php'>$pageTitle</a>";
                if (!empty($assignedToUser)) {
                    echo " &raquo; <a href='/user/$assignedToUser'>$assignedToUser</a>";
                }
                if (!empty($gameIDGiven)) {
                    echo " &raquo; <a href='/ticketmanager.php?g=$gameIDGiven'>$gameTitle ($consoleName)</a>";
                    if (!empty($achievementIDGiven)) {
                        echo " &raquo; $achievementTitle";
                    }
                }
            } else {
                echo "<a href='/ticketmanager.php'>$pageTitle</a>";
                echo " &raquo; <b>Inspect Ticket</b>";
            }
        }
        echo "</div>";

        if ($gamesTableFlag == 1) {
            echo "<h3>Top " . count($ticketData) . " Games Sorted By Most Outstanding Tickets</h3>";
        } else {
            $assignedToUser = requestInputSanitized('u', null);
            if (!isValidUsername($assignedToUser)) {
                $assignedToUser = null;
            }
            sanitize_outputs(
                $assignedToUser,
            );
            if ($gamesTableFlag == 5) {
                $openTicketsCount = countOpenTickets(true);
                $filteredTicketsCount = countOpenTickets(true, $ticketFilters, $assignedToUser, $gameIDGiven);
                if ($ticketID == 0) {
                    echo "<h3 class='longheader'>$pageTitle - " . $openTicketsCount . " Open Unofficial Ticket" . ($openTicketsCount == 1 ? "" : "s") . "</h3>";
                } else {
                    echo "<h3 class='longheader'>Inspect Ticket</h3>";
                }
            } else {
                $openTicketsCount = countOpenTickets();
                $filteredTicketsCount = countOpenTickets(false, $ticketFilters, $assignedToUser, $gameIDGiven);
                if ($ticketID == 0) {
                    echo "<h3 class='longheader'>$pageTitle - " . $openTicketsCount . " Open Ticket" . ($openTicketsCount == 1 ? "" : "s") . "</h3>";
                } else {
                    echo "<h3 class='longheader'>Inspect Ticket</h3>";
                }
            }
        }

        echo "<div class='detaillist'>";

        if ($gamesTableFlag == 1) {
            echo "<p><b>If you're a developer and find games that you love in this list, consider helping to resolve their tickets.</b></p>";
            echo "<table><tbody>";

            echo "<th>Game</th>";
            echo "<th>Number of Open Tickets</th>";

            $rowCount = 0;

            foreach ($ticketData as $nextTicket) {
                $gameID = $nextTicket['GameID'];
                $gameTitle = $nextTicket['GameTitle'];
                $gameBadge = $nextTicket['GameIcon'];
                $consoleName = $nextTicket['Console'];
                $openTickets = $nextTicket['OpenTickets'];

                sanitize_outputs(
                    $gameTitle,
                    $consoleName,
                );

                if ($rowCount++ % 2 == 0) {
                    echo "<tr>";
                } else {
                    echo "<tr>";
                }

                echo "<td>";
                echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameBadge, $consoleName);
                echo "</td>";
                echo "<td><a href='/ticketmanager.php?g=$gameID'>$openTickets</a></td>";

                echo "</tr>";
            }
            echo "</tbody></table>";
        } else {
            if ($ticketID == 0) {
                echo "<h4>Filters - " . $filteredTicketsCount . " Ticket" . ($filteredTicketsCount == 1 ? "" : "s") . " Filtered</h4>";
                echo "<div class='embedded mb-1'>";

                /*
                    Each filter is represented by a bit in the $ticketFilters variable.
                    This allows us to easily determine which filters are active as well as toggle them back and forth.
                 */
                $openTickets = ($ticketFilters & (1 << 0));
                $closedTickets = ($ticketFilters & (1 << 1));
                $resolvedTickets = ($ticketFilters & (1 << 2));
                $triggeredTickets = ($ticketFilters & (1 << 3));
                $didNotTriggerTickets = ($ticketFilters & (1 << 4));
                $md5KnownTickets = ($ticketFilters & (1 << 5));
                $md5UnknownTickets = ($ticketFilters & (1 << 6));
                $raEmulatorTickets = ($ticketFilters & (1 << 7));
                $rarchKnownTickets = ($ticketFilters & (1 << 8));
                $rarchUnknownTickets = ($ticketFilters & (1 << 9));
                $emulatorUnknownTickets = ($ticketFilters & (1 << 10));

                //State Filters
                echo "<div>";
                echo "<b>Ticket State:</b> ";
                if ($openTickets) {
                    echo "<b><a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters & ~(1 << 0)) . "'>*Open</a></b> | ";
                } else {
                    echo "<a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters | (1 << 0)) . "'>Open</a> | ";
                }

                if ($closedTickets) {
                    echo "<b><a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters & ~(1 << 1)) . "'>*Closed</a></b> | ";
                } else {
                    echo "<a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters | (1 << 1)) . "'>Closed</a> | ";
                }

                if ($resolvedTickets) {
                    echo "<b><a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters & ~(1 << 2)) . "'>*Resolved</a></b>";
                } else {
                    echo "<a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters | (1 << 2)) . "'>Resolved</a>";
                }
                echo "</div>";

                //Report Type Filters
                echo "<div>";
                echo "<b>Report Type:</b> ";
                if ($triggeredTickets) {
                    echo "<b><a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters & ~(1 << 3)) . "'>*Triggered at wrong time</a></b> | ";
                } else {
                    echo "<a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters | (1 << 3)) . "'>Triggered at wrong time</a> | ";
                }

                if ($didNotTriggerTickets) {
                    echo "<b><a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters & ~(1 << 4)) . "'>*Doesn't Trigger</a></b>";
                } else {
                    echo "<a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters | (1 << 4)) . "'>Doesn't Trigger</a>";
                }
                echo "</div>";

                //MD5 Filters
                echo "<div>";
                echo "<b>MD5:</b> ";
                if ($md5KnownTickets) {
                    echo "<b><a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters & ~(1 << 5)) . "'>*Contains MD5</a></b> | ";
                } else {
                    echo "<a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters | (1 << 5)) . "'>Contains MD5</a> | ";
                }

                if ($md5UnknownTickets) {
                    echo "<b><a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters & ~(1 << 6)) . "'>*MD5 Unknown</a></b>";
                } else {
                    echo "<a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters | (1 << 6)) . "'>MD5 Unknown</a>";
                }
                echo "</div>";

                //Emulator Filters
                echo "<div>";
                echo "<b>Emulator:</b> ";
                if ($raEmulatorTickets) {
                    echo "<b><a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters & ~(1 << 7)) . "'>*RA Emulator</a></b> | ";
                } else {
                    echo "<a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters | (1 << 7)) . "'>RA Emulator</a> | ";
                }

                if ($rarchKnownTickets) {
                    echo "<b><a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters & ~(1 << 8)) . "'>*RetroArch - Defined</a></b> | ";
                } else {
                    echo "<a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters | (1 << 8)) . "'>RetroArch - Defined</a> | ";
                }

                if ($rarchUnknownTickets) {
                    echo "<b><a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters & ~(1 << 9)) . "'>*RetroArch - Undefined</a></b> | ";
                } else {
                    echo "<a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters | (1 << 9)) . "'>RetroArch - Undefined</a> | ";
                }

                if ($emulatorUnknownTickets) {
                    echo "<b><a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters & ~(1 << 10)) . "'>*Emulator Unknown</a></b>";
                } else {
                    echo "<a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=" . ($ticketFilters | (1 << 10)) . "'>Emulator Unknown</a>";
                }
                echo "</div>";

                //Core/Unofficial Filters - These filters are mutually exclusive
                echo "<div>";
                echo "<b>Achievement State:</b> ";
                if ($gamesTableFlag != 5) {
                    echo "<b><a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=3&t=$ticketFilters'>*Core</a></b> | ";
                } else {
                    echo "<a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=3&t=$ticketFilters'>Core</a> | ";
                }

                if ($gamesTableFlag == 5) {
                    echo "<b><a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=5&t=$ticketFilters'>*Unofficial</a></b>";
                } else {
                    echo "<a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=5&t=$ticketFilters'>Unofficial</a>";
                }
                echo "</div>";

                //Clear Filter
                if ($ticketFilters != $defaultFilter || $gamesTableFlag == 5) {
                    echo "<div>";
                    echo "<a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=3&t=" . $defaultFilter . "'>Clear Filter</a>";
                    echo "</div>";
                }
                echo "</div>";

                if (isset($user) || !empty($assignedToUser)) {
                    echo "<p><b>Developer:</b> ";
                    if (isset($user)) {
                        if ($assignedToUser == $user) {
                            echo "<b>$user</b> | ";
                        } else {
                            echo "<a href='/ticketmanager.php?&u=$user&g=$gameIDGiven&f=$gamesTableFlag&t=$ticketFilters'>$user</a> | ";
                        }
                    }

                    if (!empty($assignedToUser) && $assignedToUser !== $user) {
                        echo "<b>$assignedToUser</b> | ";
                    }

                    if (!empty($assignedToUser)) {
                        echo "<a href='/ticketmanager.php?&g=$gameIDGiven&f=$gamesTableFlag&t=$ticketFilters'>Clear Filter</a>";
                    } else {
                        echo "<b>Clear Filter</b>";
                    }
                    echo "</p>";
                }

                if (!empty($gameIDGiven)) {
                    echo "<p><b>Game</b>";
                    if (!empty($achievementIDGiven)) {
                        echo "<b>/Achievement</b>: ";
                        echo "<a href='/ticketmanager.php?g=$gameIDGiven'>$gameTitle ($consoleName)</a>";
                        echo " | <b>$achievementTitle</b>";
                    } else {
                        echo ": <b>$gameTitle ($consoleName)</b>";
                    }
                    echo " | <a href='/ticketmanager.php?&u=$assignedToUser&f=$gamesTableFlag&t=$ticketFilters'>Clear Filter</a></p>";
                }

                echo "<table><tbody>";

                echo "<th>ID</th>";
                echo "<th>State</th>";
                echo "<th>Achievement</th>";
                echo "<th>Game</th>";
                echo "<th>Developer</th>";
                echo "<th>Reporter</th>";
                echo "<th class='text-nowrap'>Reported At</th>";

                $rowCount = 0;

                foreach ($ticketData as $nextTicket) {
                    //var_dump( $nextTicket );
                    //$query = "SELECT ach.ID, ach.Title AS AchievementTitle, ach.Description, ach.Points, ach.Author, ach.DateCreated, ach.DateModified, ach.BadgeName, ach.GameID, gd.Title AS GameTitle, gd.ConsoleID, c.Name AS ConsoleName ";

                    $ticketID = $nextTicket['ID'];
                    $achID = $nextTicket['AchievementID'];
                    $achTitle = $nextTicket['AchievementTitle'];
                    $achDesc = $nextTicket['AchievementDesc'];
                    $achAuthor = $nextTicket['AchievementAuthor'];
                    $achPoints = $nextTicket['Points'];
                    $achBadgeName = $nextTicket['BadgeName'];
                    $gameID = $nextTicket['GameID'];
                    $gameTitle = $nextTicket['GameTitle'];
                    $gameBadge = $nextTicket['GameIcon'];
                    $consoleName = $nextTicket['ConsoleName'];
                    $reportType = $nextTicket['ReportType'];
                    $reportNotes = str_replace('<br>', "\n", $nextTicket['ReportNotes']);
                    $reportState = $nextTicket['ReportState'];

                    $reportedAt = $nextTicket['ReportedAt'];
                    $niceReportedAt = getNiceDate(strtotime($reportedAt));
                    $reportedBy = $nextTicket['ReportedBy'];
                    $resolvedAt = $nextTicket['ResolvedAt'];
                    $niceResolvedAt = getNiceDate(strtotime($resolvedAt));
                    $resolvedBy = $nextTicket['ResolvedBy'];

                    sanitize_outputs(
                        $achTitle,
                        $achDesc,
                        $achAuthor,
                        $gameTitle,
                        $consoleName,
                        $reportNotes,
                    );

                    if ($rowCount++ % 2 == 0) {
                        echo "<tr>";
                    } else {
                        echo "<tr>";
                    }

                    echo "<td>";
                    echo "<a href='/ticketmanager.php?i=$ticketID'>$ticketID</a>";
                    echo "</td>";

                    echo "<td>";
                    echo $reportStates[$reportState];
                    echo "</td>";

                    echo "<td style='min-width:25%'>";
                    echo GetAchievementAndTooltipDiv($achID, $achTitle, $achDesc, $achPoints, $gameTitle, $achBadgeName, true);
                    echo "</td>";

                    echo "<td>";
                    echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameBadge, $consoleName);
                    echo "</td>";

                    echo "<td>";
                    echo GetUserAndTooltipDiv($achAuthor, true);
                    echo GetUserAndTooltipDiv($achAuthor, false);
                    echo "</td>";
                    echo "<td>";
                    echo GetUserAndTooltipDiv($reportedBy, true);
                    echo GetUserAndTooltipDiv($reportedBy, false);
                    echo "</td>";

                    echo "<td class='smalldate'>";
                    echo $niceReportedAt;
                    echo "</td>";

                    // echo "<td>";
                    // echo $reportNotes;
                    // echo "</td>";

                    echo "</tr>";
                }

                echo "</tbody></table>";
                echo "</div>";

                echo "<div class='rightalign row'>";
                if ($offset > 0) {
                    $prevOffset = $offset - $maxCount;
                    if ($prevOffset < 0) {
                        $prevOffset = 0;
                    }
                    echo "<a href='/ticketmanager.php?g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=$ticketFilters'>First</a> - ";
                    echo "<a href='/ticketmanager.php?o=$prevOffset&g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=$ticketFilters'>&lt; Previous $maxCount</a> - ";
                }
                if ($rowCount == $maxCount) {
                    //	Max number fetched, i.e. there are more. Can goto next $maxCount.
                    $nextOffset = $offset + $maxCount;
                    echo "<a href='/ticketmanager.php?o=$nextOffset&g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=$ticketFilters'>Next $maxCount &gt;</a>";
                    echo " - <a href='/ticketmanager.php?o=" . ($filteredTicketsCount - ($maxCount - 1)) . "&g=$gameIDGiven&u=$assignedToUser&f=$gamesTableFlag&t=$ticketFilters'>Last</a>";
                }
                echo "</div>";
            } else {
                $nextTicket = $ticketData;
                $ticketID = $nextTicket['ID'];
                $achID = $nextTicket['AchievementID'];
                $achTitle = $nextTicket['AchievementTitle'];
                $achDesc = $nextTicket['AchievementDesc'];
                $achAuthor = $nextTicket['AchievementAuthor'];
                $achPoints = $nextTicket['Points'];
                $achBadgeName = $nextTicket['BadgeName'];
                $gameID = $nextTicket['GameID'];
                $gameTitle = $nextTicket['GameTitle'];
                $gameBadge = $nextTicket['GameIcon'];
                $consoleName = $nextTicket['ConsoleName'];
                $reportState = $nextTicket['ReportState'];
                $reportType = $nextTicket['ReportType'];
                $reportNotes = str_replace('<br>', "\n", $nextTicket['ReportNotes']);

                $reportedAt = $nextTicket['ReportedAt'];
                $niceReportedAt = getNiceDate(strtotime($reportedAt));
                $reportedBy = $nextTicket['ReportedBy'];
                $resolvedAt = $nextTicket['ResolvedAt'];
                $niceResolvedAt = getNiceDate(strtotime($resolvedAt));
                $resolvedBy = $nextTicket['ResolvedBy'];

                sanitize_outputs(
                    $achTitle,
                    $achDesc,
                    $achAuthor,
                    $gameTitle,
                    $consoleName,
                    $reportNotes,
                );

                echo "<table><tbody>";

                echo "<th>ID</th>";
                echo "<th>State</th>";
                echo "<th>Achievement</th>";
                echo "<th>Game</th>";
                echo "<th>Developer</th>";
                echo "<th>Reporter</th>";
                echo "<th>Reported At</th>";

                echo "<tr>";

                echo "<td>";
                echo "<a href='/ticketmanager.php?i=$ticketID'>$ticketID</a>";
                echo "</td>";

                echo "<td>";
                echo $reportStates[$reportState];
                echo "</td>";

                echo "<td style='min-width:25%'>";
                echo GetAchievementAndTooltipDiv($achID, $achTitle, $achDesc, $achPoints, $gameTitle, $achBadgeName, true);
                echo "</td>";

                echo "<td style='min-width:25%'>";
                echo GetGameAndTooltipDiv($gameID, $gameTitle, $gameBadge, $consoleName);
                echo "</td>";

                echo "<td>";
                echo GetUserAndTooltipDiv($achAuthor, true);
                echo GetUserAndTooltipDiv($achAuthor, false);
                echo "</td>";

                echo "<td>";
                echo GetUserAndTooltipDiv($reportedBy, true);
                echo GetUserAndTooltipDiv($reportedBy, false);
                echo "</td>";

                echo "<td class='smalldate'>";
                echo $niceReportedAt;
                echo "</td>";

                echo "</tr>";

                echo "<tr>";
                echo "<td>";
                echo "Notes: ";
                echo "</td>";
                echo "<td colspan='6'>";
                echo "<code>$reportNotes</code>";
                echo "</td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td>";
                echo "Report Type: ";
                echo "</td>";
                echo "<td colspan='6'>";
                echo ($reportType == 1) ? "<b>Triggered at wrong time</b>" : "<b>Doesn't Trigger</b>";
                echo "</td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td></td><td colspan='6'>";
                echo "<div class='temp'>";
                echo "<a href='ticketmanager.php?g=$gameID'>View other tickets for this game</a>";
                echo "</div>";
                echo "</td>";
                echo "</tr>";

                if ($numOpenTickets > 0 || $numClosedTickets > 0) {
                    if ($numOpenTickets > 0) {
                        echo "<tr>";
                        echo "<td></td><td colspan='6'>";
                        echo "Found $numOpenTickets other open tickets for this achievement: ";

                        foreach ($altTicketData as $nextTicket) {
                            $nextTicketID = $nextTicket['ID'];
                            settype($nextTicketID, 'integer');
                            settype($ticketID, 'integer');

                            if ($nextTicketID !== $ticketID && ($nextTicket['ReportState'] == 1)) {
                                echo "<a href='ticketmanager.php?i=$nextTicketID'>$nextTicketID</a>, ";
                            }
                        }

                        echo "</td>";
                        echo "</tr>";
                    }
                    if ($numClosedTickets > 0) {
                        echo "<tr>";
                        echo "<td></td><td colspan='6'>";
                        echo "Found $numClosedTickets closed tickets for this achievement: ";

                        foreach ($altTicketData as $nextTicket) {
                            $nextTicketID = $nextTicket['ID'];
                            settype($nextTicketID, 'integer');
                            settype($ticketID, 'integer');
                            settype($nextTicket['ReportState'], 'integer');

                            if ($nextTicketID !== $ticketID && ($nextTicket['ReportState'] !== 1)) {
                                echo "<a href='ticketmanager.php?i=$nextTicketID'>$nextTicketID</a>, ";
                            }
                        }

                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr>";
                    echo "<td></td><td colspan='6'>";
                    echo "<div class='temp'>";
                    echo "No other tickets found for this achievement";
                    echo "</div>";
                    echo "</td>";
                    echo "</tr>";
                }

                echo "<tr>";
                echo "<td></td><td colspan='6'>";
                echo "<div class='temp'>";
                $awardCount = getAwardsSince($achID, $reportedAt);
                echo "This achievement has been earned " . $awardCount['softcoreCount'] . " <b>(" . $awardCount['hardcoreCount'] . ")</b> "
                    . ($awardCount['hardcoreCount'] == 1 ? "time" : "times") . " since this ticket was created.";
                echo "</div>";
                echo "</td>";
                echo "</tr>";

                if ($permissions >= \RA\Permissions::Developer) {
                    echo "<tr>";

                    echo "<td>Reporter:</td>";
                    echo "<td colspan='6'>";
                    echo "<div class='smallicon'>";
                    echo "<span>";
                    $msgPayload = "Hi [user=$reportedBy], I'm contacting you about ticket retroachievements.org/ticketmanager.php?i=$ticketID ";
                    $msgPayload = rawurlencode($msgPayload);
                    echo "<a href='createmessage.php?t=$reportedBy&amp;s=Bug%20Report%20($gameTitle)&p=$msgPayload'>Contact the reporter - $reportedBy</a>";
                    echo "</span>";
                    echo "</div>";
                    echo "</td>";
                    echo "</tr>";
                }

                echo "<tr>";
                echo "<td></td><td colspan='6'>";

                if (getUserUnlockAchievement($reportedBy, $achID, $unlockData)) {
                    echo "$reportedBy earned this achievement at " . getNiceDate(strtotime($unlockData[0]['Date']));
                    if ($unlockData[0]['Date'] >= $reportedAt) {
                        echo " (after the report).";
                    } else {
                        echo " (before the report).";
                    }
                } else {
                    echo "$reportedBy did not earn this achievement.";
                }

                if ($user == $reportedBy || $permissions >= \RA\Permissions::Developer) {
                    echo "<tr>";

                    echo "<td>Action: </td><td colspan='6'>";
                    echo "<div class='smallicon'>";
                    echo "<span>";

                    echo "<b>Please, add some comments about the action you're going to take.</b><br>";
                    echo "<form method=post action='ticketmanager.php?i=$ticketID'>";
                    echo "<input type='hidden' name='i' value='$ticketID'>";

                    echo "<select name='action' required>";
                    echo "<option value='' disabled selected hidden>Choose an action...</option>";
                    if ($reportState == 1) {
                        if ($user == $reportedBy) { // only the reporter can close as a mistaken report
                            echo "<option value='closed-mistaken'>Close - Mistaken report</option>";
                        }

                        if ($permissions >= \RA\Permissions::Developer) {
                            echo "<option value='resolved'>Resolve as fixed (add comments about your fix below)</option>";
                            echo "<option value='demoted'>Demote achievement to Unofficial</option>";
                            echo "<option value='network'>Close - Network problems</option>";
                            echo "<option value='not-enough-info'>Close - Not enough information</option>";
                            echo "<option value='wrong-rom'>Close - Wrong ROM</option>";
                            echo "<option value='unable-to-reproduce'>Close - Unable to reproduce</option>";
                            echo "<option value='closed-other'>Close - Another reason (add comments below)</option>";
                        }
                    } else { // ticket is not open
                        echo "<option value='reopen'>Reopen this ticket</option>";
                    }

                    echo "</select>";

                    echo " <input type='submit' value='Perform action'>";
                    echo "</form>";

                    echo "</span>";
                    echo "</div>";
                    echo "</td>";
                    echo "</tr>";
                }
                echo "<tr>";
                echo "<td colspan='5'>";
                echo "<div class='commentscomponent'>";

                echo "<h4>Comments</h4>";
                $forceAllowDeleteComments = $permissions >= \RA\Permissions::Admin;
                RenderCommentsComponent($user, $numArticleComments, $commentData, $ticketID, \RA\ArticleType::AchievementTicket, $forceAllowDeleteComments);

                echo "</div>";
                echo "</td>";
                echo "</tr>";

                echo "</tbody></table>";
                echo "</div>";

                if ($permissions >= \RA\Permissions::Developer && getAchievementMetadata($achID, $dataOut)) {
                    getCodeNotes($gameID, $codeNotes);
                    $achMem = $dataOut['MemAddr'];
                    echo "<div class='devbox'>";
                    echo "<span onclick=\"$('#devboxcontent').toggle(); return false;\">Click to show achievement logic:</span><br>";
                    echo "<div id='devboxcontent'>";

                    echo "<div style='clear:both;'></div>";
                    echo "<li> Achievement ID: " . $achID . "</li>";
                    echo "<div>";
                    echo "<li>Mem:</li>";
                    echo "<code>" . htmlspecialchars($achMem) . "</code>";
                    echo "<li>Mem explained:</li>";
                    echo "<code>" . getAchievementPatchReadableHTML($achMem, $codeNotes) . "</code>";
                    echo "</div>";

                    echo "</div>"; //   devboxcontent
                    echo "</div>"; //   devbox
                }
            }
        }
        echo "</div>";
        ?>
        <br>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
