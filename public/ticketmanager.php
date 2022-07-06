<?php

use RA\AchievementType;
use RA\ArticleType;
use RA\Permissions;
use RA\TicketFilters;
use RA\TicketState;
use RA\TicketType;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    header("Location: " . getenv('APP_URL'));
    exit;
}

$maxCount = 50;
$count = requestInputSanitized('c', $maxCount, 'integer');
$offset = requestInputSanitized('o', 0, 'integer');

$ticketID = requestInputSanitized('i', 0, 'integer');
$defaultFilter = TicketFilters::Default;
$ticketFilters = requestInputSanitized('t', $defaultFilter, 'integer');

$reportModes = ["Softcore", "Hardcore"];

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
            if ($permissions >= Permissions::Developer) {
                $ticketState = TicketState::Resolved;
            }
            break;

        case "demoted":
            if ($permissions >= Permissions::Developer) {
                $ticketState = TicketState::Closed;
                $reason = "Demoted";
            }
            break;

        case "not-enough-info":
            if ($permissions >= Permissions::Developer) {
                $ticketState = TicketState::Closed;
                $reason = "Not enough information";
            }
            break;

        case "wrong-rom":
            if ($permissions >= Permissions::Developer) {
                $ticketState = TicketState::Closed;
                $reason = "Wrong ROM";
            }
            break;

        case "network":
            if ($permissions >= Permissions::Developer) {
                $ticketState = TicketState::Closed;
                $reason = "Network problems";
            }
            break;

        case "unable-to-reproduce":
            if ($permissions >= Permissions::Developer) {
                $ticketState = TicketState::Closed;
                $reason = "Unable to reproduce";
            }
            break;

        case "closed-other":
            if ($permissions >= Permissions::Developer) {
                $ticketState = TicketState::Closed;
                $reason = "See the comments";
            }
            break;

        case "request":
            $ticketState = TicketState::Request;
            break;

        case "reopen":
            $ticketState = TicketState::Open;
            break;

        default:
            $action = null;
            break;
    }

    if ($action != null &&
        $ticketState != $ticketData['ReportState'] &&
        (
            $permissions >= Permissions::Developer ||
            $user == $ticketData['ReportedBy']
        )
    ) {
        updateTicket($user, $ticketID, $ticketState, $reason);
        $ticketData = getTicket($ticketID);
    }

    $numArticleComments = getArticleComments(ArticleType::AchievementTicket, $ticketID, 0, 20, $commentData);

    // sets all filters enabled so we get closed/resolved tickets as well
    $altTicketData = getAllTickets(0, 99, null, null, null, null, $ticketData['AchievementID'], TicketFilters::All);
    // var_dump($altTicketData);
    $numOpenTickets = 0;
    foreach ($altTicketData as $pastTicket) {
        settype($pastTicket["ID"], 'integer');

        if ($pastTicket["ReportState"] == TicketState::Open && $pastTicket["ID"] !== $ticketID) {
            $numOpenTickets++;
        }
    }

    $numClosedTickets = count($altTicketData) - $numOpenTickets - 1;
}

$assignedToUser = null;
$reportedByUser = null;
$resolvedByUser = null;
$gamesTableFlag = 0;
$gameIDGiven = 0;
$achievementIDGiven = 0;
if ($ticketID == 0) {
    $gamesTableFlag = requestInputSanitized('f', 3, 'integer');
    if ($gamesTableFlag == 1) {
        $count = requestInputSanitized('c', 100, 'integer');
        $ticketData = gamesSortedByOpenTickets($count);
    } else {
        $assignedToUser = requestInputSanitized('u', null);
        if (!isValidUsername($assignedToUser)) {
            $assignedToUser = null;
        }
        $reportedByUser = requestInputSanitized('p', null);
        if (!isValidUsername($reportedByUser)) {
            $reportedByUser = null;
        }
        $resolvedByUser = requestInputSanitized('r', null);
        if (!isValidUsername($resolvedByUser)) {
            $resolvedByUser = null;
        }
        $gameIDGiven = requestInputSanitized('g', null, 'integer');

        $achievementIDGiven = requestInputSanitized('a', null, 'integer');
        if ($achievementIDGiven > 0) {
            $achievementData = GetAchievementData($achievementIDGiven);
            $achievementTitle = $achievementData['Title'];
            $gameIDGiven = $achievementData['GameID']; // overwrite the given game ID
        }

        $ticketData = getAllTickets(
            $offset,
            $count,
            $assignedToUser,
            $reportedByUser,
            $resolvedByUser,
            $gameIDGiven,
            $achievementIDGiven,
            $ticketFilters,
            $gamesTableFlag === AchievementType::Unofficial
        );
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
<?php RenderHeader($userDetails); ?>
<div id="mainpage">
    <?php RenderErrorCodeWarning($errorCode); ?>
    <div id="fullcontainer">
        <?php
        echo "<div class='navpath'>";
        if ($gamesTableFlag === 1) {
            echo "<a href='/ticketmanager.php'>$pageTitle</a></b> &raquo; <b>Games With Open Tickets";
        } else {
            if ($ticketID == 0) {
                echo "<a href='/ticketmanager.php'>$pageTitle</a>";
                if (!empty($assignedToUser)) {
                    echo " &raquo; <a href='/user/$assignedToUser'>$assignedToUser</a>";
                }
                if (!empty($reportedByUser)) {
                    echo " &raquo; <a href='/user/$reportedByUser'>$reportedByUser</a>";
                }
                if (!empty($resolvedByUser)) {
                    echo " &raquo; <a href='/user/$resolvedByUser'>$resolvedByUser</a>";
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
            echo "<h3 class='longheader'>Top " . (is_countable($ticketData) ? count($ticketData) : 0) . " Games Sorted By Most Outstanding Tickets</h3>";
        } else {
            $assignedToUser = requestInputSanitized('u', null);
            if (!isValidUsername($assignedToUser)) {
                $assignedToUser = null;
            }
            $reportedByUser = requestInputSanitized('p', null);
            if (!isValidUsername($reportedByUser)) {
                $reportedByUser = null;
            }
            $resolvedByUser = requestInputSanitized('r', null);
            if (!isValidUsername($resolvedByUser)) {
                $resolvedByUser = null;
            }
            $openTicketsCount = countOpenTickets($gamesTableFlag === AchievementType::Unofficial);
            $filteredTicketsCount = countOpenTickets(
                $gamesTableFlag === AchievementType::Unofficial,
                $ticketFilters,
                $assignedToUser,
                $reportedByUser,
                $resolvedByUser,
                $gameIDGiven,
                $achievementIDGiven
            );
            echo "<h3 class='longheader'>$pageTitle - " . $openTicketsCount . " Open " . ($gamesTableFlag == AchievementType::Unofficial ? 'Unofficial ' : '') . " Ticket" . ($openTicketsCount == 1 ? "" : "s") . "</h3>";
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
                $closedTickets = ($ticketFilters & TicketFilters::StateClosed);
                $resolvedTickets = ($ticketFilters & TicketFilters::StateResolved);

                sanitize_outputs($assignedToUser, $reportedByUser, $resolvedByUser);

                // State Filters
                $createLink = function ($flag, $value, $flag2 = null, $value2 = null) use ($gameIDGiven, $achievementIDGiven, $assignedToUser, $reportedByUser, $resolvedByUser, $gamesTableFlag, $ticketFilters) {
                    $appendParam = function (&$link, $param, $fallback, $default) use ($flag, $value, $flag2, $value2) {
                        $param_value = ($flag == $param) ? $value : (($flag2 == $param) ? $value2 : $fallback);
                        if ($param_value != $default) {
                            $link .= str_contains($link, '?') ? '&' : '?';
                            $link .= $param . '=' . $param_value;
                        }
                    };

                    $link = "/ticketmanager.php";
                    $appendParam($link, 'g', $gameIDGiven, null);
                    $appendParam($link, 'a', $achievementIDGiven, null);
                    $appendParam($link, 'u', $assignedToUser, null);
                    $appendParam($link, 'p', $reportedByUser, null);
                    $appendParam($link, 'r', $resolvedByUser, null);
                    $appendParam($link, 'f', $gamesTableFlag, 3);
                    $appendParam($link, 't', $ticketFilters, TicketFilters::Default);

                    return $link;
                };

                $linkFilter = function (string $label, int $ticketFilter) use ($ticketFilters, $createLink) {
                    if ($ticketFilters & $ticketFilter) {
                        // when clearing Closed or Resolved bit and the other is not set, also clear ResolvedByNonAuthor bit
                        if (($ticketFilters & (TicketFilters::StateClosed | TicketFilters::StateResolved)) == $ticketFilter) {
                            $ticketFilter |= TicketFilters::ResolvedByNonAuthor;
                        }

                        return "<b><a href='" . $createLink('t', $ticketFilters & ~$ticketFilter) . "'>*$label</a></b>";
                    } else {
                        return "<a href='" . $createLink('t', $ticketFilters | $ticketFilter) . "'>$label</a>";
                    }
                };

                echo "<div>";
                echo "<b>Ticket State:</b> ";
                echo $linkFilter(TicketState::toString(TicketState::Open), TicketFilters::StateOpen) . ' | ';
                echo $linkFilter(TicketState::toString(TicketState::Request), TicketFilters::StateRequest) . ' | ';
                echo $linkFilter(TicketState::toString(TicketState::Closed), TicketFilters::StateClosed) . ' | ';
                echo $linkFilter(TicketState::toString(TicketState::Resolved), TicketFilters::StateResolved);
                echo "</div>";

                // Report Type Filters
                echo "<div>";
                echo "<b>Report Type:</b> ";
                echo $linkFilter(TicketType::toString(TicketType::TriggeredAtWrongTime), TicketFilters::TypeTriggeredAtWrongTime) . ' | ';
                echo $linkFilter(TicketType::toString(TicketType::DidNotTrigger), TicketFilters::TypeDidNotTrigger);
                echo "</div>";

                // Hash Filters
                echo "<div>";
                echo "<b>Hash:</b> ";
                echo $linkFilter('Contains Hash', TicketFilters::HashKnown) . ' | ';
                echo $linkFilter('Hash Unknown', TicketFilters::HashUnknown);
                echo "</div>";

                // Emulator Filters
                echo "<div>";
                echo "<b>Emulator:</b> ";
                echo $linkFilter('RA Emulator', TicketFilters::EmulatorRA) . ' | ';
                echo $linkFilter('RetroArch - Core Specified', TicketFilters::EmulatorRetroArchCoreSpecified) . ' | ';
                echo $linkFilter('RetroArch - Core Not Specified', TicketFilters::EmulatorRetroArchCoreNotSpecified) . ' | ';
                echo $linkFilter('Unknown', TicketFilters::EmulatorUnknown);
                echo "</div>";

                // Core/Unofficial Filters - These filters are mutually exclusive
                echo "<div>";
                echo "<b>Achievement State:</b> ";
                if ($gamesTableFlag != 5) {
                    echo "<b><a href='" . $createLink('f', 5) . "'>*Core</a></b> | ";
                    echo "<a href='" . $createLink('f', 5) . "'>Unofficial</a>";
                } else {
                    echo "<a href='" . $createLink('f', 3) . "'>Core</a> | ";
                    echo "<b><a href='" . $createLink('f', 3) . "'>*Unofficial</a></b>";
                }
                echo "</div>";

                // Mode Filters
                echo "<div>";
                echo "<b>Mode:</b> ";
                echo $linkFilter('Unknown', TicketFilters::HardcoreUnknown) . ' | ';
                echo $linkFilter('Hardcore', TicketFilters::HardcoreOn) . ' | ';
                echo $linkFilter('Softcore', TicketFilters::HardcoreOff);
                echo "</div>";

                // Active Dev Filters
                echo "<div>";
                echo "<b>Dev Status:</b> ";
                echo $linkFilter('Inactive', TicketFilters::DevInactive) . ' | ';
                echo $linkFilter('Active', TicketFilters::DevActive) . ' | ';
                echo $linkFilter('Junior', TicketFilters::DevJunior);
                echo "</div>";

                // Resolved By Filter
                if ($closedTickets || $resolvedTickets) {
                    echo "<div>";
                    echo "<b>Resolved By:</b> ";
                    echo $linkFilter('Not Achievement Developer', TicketFilters::ResolvedByNonAuthor);
                    echo "</div>";
                }

                // Clear Filter
                if ($ticketFilters != $defaultFilter || $gamesTableFlag === AchievementType::Unofficial) {
                    echo "<div>";
                    echo "<a href='" . $createLink('t', $defaultFilter, 'f', 3) . "'>Clear Filter</a>";
                    echo "</div>";
                }
                echo "</div>";

                $userFilter = function ($label, $param, $filteredUser) use ($createLink, $user) {
                    if (isset($user) || !empty($filteredUser)) {
                        echo "<form class='form-inline' action='" . $createLink($param, null) . "' method='POST'>";

                        echo "<p><b>$label:</b> ";
                        if (isset($user)) {
                            if ($filteredUser == $user) {
                                echo "<b><a href='" . $createLink($param, null) . "'>*$user</a></b> | ";
                            } else {
                                echo "<a href='" . $createLink($param, $user) . "'>$user</a> | ";
                            }
                        }

                        if (!empty($filteredUser) && $filteredUser !== $user) {
                            echo "<b><a href='" . $createLink($param, null) . "'>*$filteredUser</a></b> | ";
                        }

                        echo "<input size='28' name='$param' type='text' value=''>";
                        echo "&nbsp";
                        echo "<input type='submit' value='Select'>";
                        echo "</p>";
                        echo "</form>";
                    }
                };

                $userFilter('Developer', 'u', $assignedToUser);
                $userFilter('Reporter', 'p', $reportedByUser);

                if ($closedTickets || $resolvedTickets) {
                    $userFilter('Resolver', 'r', $resolvedByUser);
                }

                if (!empty($gameIDGiven)) {
                    $noGameFilterUrl = "/ticketmanager.php?g=&u=$assignedToUser&p=$reportedByUser&r=$resolvedByUser&f=$gamesTableFlag&t=";
                    echo "<div class='embedded mb-1'>";

                    echo "<div><b>Game:</b> ";
                    echo "<b><a href='" . $createLink('g', null, 'a', null) . "'>*$gameTitle ($consoleName)</a></b>";
                    echo "</div>";

                    if (!empty($achievementIDGiven)) {
                        echo "<div><b>Achievement:</b> ";
                        echo "<b><a href='" . $createLink('a', null) . "'>*$achievementTitle</a></b>";
                        echo "</div>";
                    }

                    echo "</div>";
                }

                echo "<table><tbody>";

                echo "<th>ID</th>";
                echo "<th>State</th>";
                echo "<th>Achievement</th>";
                echo "<th>Game</th>";
                echo "<th>Developer</th>";
                echo "<th>Reporter</th>";
                if ($closedTickets || $resolvedTickets) {
                    echo "<th>Resolver</th>";
                }
                echo "<th class='text-nowrap'>Reported At</th>";

                $rowCount = 0;

                foreach ($ticketData as $nextTicket) {
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
                    if ($closedTickets || $resolvedTickets) {
                        $resolvedBy = $nextTicket['ResolvedBy'];
                    }
                    sanitize_outputs(
                        $achTitle,
                        $achDesc,
                        $achAuthor,
                        $gameTitle,
                        $consoleName,
                        $reportNotes,
                        $reportedBy,
                        $resolvedBy
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
                    echo TicketState::toString($reportState);
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
                    if ($closedTickets || $resolvedTickets) {
                        echo "<td>";
                        echo GetUserAndTooltipDiv($resolvedBy, true);
                        echo GetUserAndTooltipDiv($resolvedBy, false);
                        echo "</td>";
                    }

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
                $baseLink = $createLink(null, null);
                $baseLink .= (str_contains($baseLink, '?') ? '&' : '?');
                RenderPaginator($filteredTicketsCount, $maxCount, $offset, "${baseLink}o=");
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
                $mode = $nextTicket['Hardcore'];
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
                    $mode,
                    $reportNotes,
                    $reportedBy,
                    $resolvedBy
                );

                echo "<table><tbody>";

                echo "<th>ID</th>";
                echo "<th>State</th>";
                echo "<th>Achievement</th>";
                echo "<th>Game</th>";
                echo "<th>Developer</th>";
                echo "<th>Reporter</th>";
                echo "<th>Resolver</th>";
                echo "<th>Reported At</th>";

                echo "<tr>";

                echo "<td>";
                echo "<a href='/ticketmanager.php?i=$ticketID'>$ticketID</a>";
                echo "</td>";

                echo "<td>";
                echo TicketState::toString($reportState);
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

                echo "<td>";
                echo GetUserAndTooltipDiv($resolvedBy, true);
                echo GetUserAndTooltipDiv($resolvedBy, false);
                echo "</td>";

                echo "<td class='smalldate'>";
                echo $niceReportedAt;
                echo "</td>";

                echo "</tr>";

                echo "<tr>";
                echo "<td>";
                echo "Notes: ";
                echo "</td>";
                echo "<td colspan='7'>";
                echo "<code>$reportNotes</code>";
                echo "</td>";
                echo "</tr>";

                if (isset($mode)) {
                    echo "<tr>";
                    echo "<td>";
                    echo "Mode: ";
                    echo "</td>";
                    echo "<td colspan='7'>";
                    echo "<b>$reportModes[$mode]</b>";
                    echo "</td>";
                    echo "</tr>";
                }

                echo "<tr>";
                echo "<td>";
                echo "Report Type: ";
                echo "</td>";
                echo "<td colspan='7'>";
                echo ($reportType == 1) ? "<b>Triggered at wrong time</b>" : "<b>Doesn't Trigger</b>";
                echo "</td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td></td><td colspan='7'>";
                echo "<div class='temp'>";
                echo "<a href='ticketmanager.php?g=$gameID'>View other tickets for this game</a>";
                echo "</div>";
                echo "</td>";
                echo "</tr>";

                if ($numOpenTickets > 0 || $numClosedTickets > 0) {
                    if ($numOpenTickets > 0) {
                        echo "<tr>";
                        echo "<td></td><td colspan='7'>";
                        echo "Found $numOpenTickets other open tickets for this achievement: ";

                        foreach ($altTicketData as $nextTicket) {
                            $nextTicketID = $nextTicket['ID'];
                            settype($nextTicketID, 'integer');
                            settype($ticketID, 'integer');

                            if ($nextTicketID !== $ticketID && ($nextTicket['ReportState'] == TicketState::Open)) {
                                echo "<a href='ticketmanager.php?i=$nextTicketID'>$nextTicketID</a>, ";
                            }
                        }

                        echo "</td>";
                        echo "</tr>";
                    }
                    if ($numClosedTickets > 0) {
                        echo "<tr>";
                        echo "<td></td><td colspan='7'>";
                        echo "Found $numClosedTickets closed tickets for this achievement: ";

                        foreach ($altTicketData as $nextTicket) {
                            $nextTicketID = $nextTicket['ID'];
                            settype($nextTicketID, 'integer');
                            settype($ticketID, 'integer');
                            settype($nextTicket['ReportState'], 'integer');

                            if ($nextTicketID !== $ticketID && ($nextTicket['ReportState'] !== TicketState::Open)) {
                                echo "<a href='ticketmanager.php?i=$nextTicketID'>$nextTicketID</a>, ";
                            }
                        }

                        echo "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr>";
                    echo "<td></td><td colspan='7'>";
                    echo "<div class='temp'>";
                    echo "No other tickets found for this achievement";
                    echo "</div>";
                    echo "</td>";
                    echo "</tr>";
                }

                echo "<tr>";
                echo "<td></td><td colspan='7'>";
                echo "<div class='temp'>";
                $awardCount = getUnlocksSince((int) $achID, $reportedAt);
                echo "This achievement has been earned " . $awardCount['softcoreCount'] . " <b>(" . $awardCount['hardcoreCount'] . ")</b> "
                    . ($awardCount['hardcoreCount'] == 1 ? "time" : "times") . " since this ticket was created.";
                echo "</div>";
                echo "</td>";
                echo "</tr>";

                if ($permissions >= Permissions::Developer) {
                    echo "<tr>";

                    echo "<td>Reporter:</td>";
                    echo "<td colspan='7'>";
                    echo "<div class='smallicon'>";
                    $msgPayload = "Hi [user=$reportedBy], I'm contacting you about ticket retroachievements.org/ticketmanager.php?i=$ticketID ";
                    $msgPayload = rawurlencode($msgPayload);
                    $msgTitle = rawurlencode("Bug Report ($gameTitle)");
                    echo "<a href='createmessage.php?t=$reportedBy&amp;s=$msgTitle&p=$msgPayload'>Contact the reporter - $reportedBy</a>";
                    echo "</div>";
                    echo "</td>";
                    echo "</tr>";

                    if ($reportState == TicketState::Open) {
                        $lastComment = null;
                        foreach ($commentData as $comment) {
                            if ($comment['User'] != 'Server') {
                                $lastComment = $comment;
                            }
                        }
                        if ($lastComment != null && ($lastComment['User'] == $user || $lastComment['User'] == $achAuthor)) {
                            echo "<tr><td/><td colspan='6'>";
                            echo "<div class='smallicon'>";
                            echo "<a href='ticketmanager.php?i=$ticketID&action=request'>Transfer to reporter - $reportedBy</a>";
                            echo "</div>";
                            echo "</td></tr>";
                        }
                    } elseif ($reportState == TicketState::Request) {
                        echo "<tr><td/><td colspan='6'>";
                        echo "<div class='smallicon'>";
                        echo "<a href='ticketmanager.php?i=$ticketID&action=reopen'>Transfer to author - $achAuthor</a>";
                        echo "</div>";
                        echo "</td></tr>";
                    }
                }

                echo "<tr>";
                if ($permissions >= Permissions::Developer) {
                    echo "<td></td>";
                } else {
                    echo "<td>Reporter:</td>";
                }
                echo "<td colspan='7'>";

                $numAchievements = getUserUnlockDates($reportedBy, $gameID, $unlockData);
                $unlockData[] = ['ID' => 0, 'Title' => 'Ticket Created', 'Date' => $reportedAt, 'HardcoreMode' => 0];
                usort($unlockData, fn ($a, $b) => strtotime($b["Date"]) - strtotime($a["Date"]));

                $unlockDate = null;
                foreach ($unlockData as $unlockEntry) {
                    if ($unlockEntry['ID'] == $achID) {
                        $unlockDate = $unlockEntry['Date'];
                        break;
                    }
                }

                if ($unlockDate != null) {
                    echo "$reportedBy earned this achievement at " . getNiceDate(strtotime($unlockDate));
                    if ($unlockDate >= $reportedAt) {
                        echo " (after the report).";
                    } else {
                        echo " (before the report).";
                    }
                } elseif ($numAchievements == 0) {
                    echo "$reportedBy has not earned any achievements for this game.";
                } else {
                    echo "$reportedBy did not earn this achievement.";
                }
                echo "</td></tr>";

                if ($numAchievements > 0 && $permissions >= Permissions::Developer) {
                    echo "<tr><td></td><td colspan='7'>";

                    echo "<div class='devbox'>";
                    echo "<span onclick=\"$('#unlockhistory').toggle(); return false;\">Click to show player unlock history for this game</span><br>";
                    echo "<div id='unlockhistory' style='display: none'>";
                    echo "<table>";

                    foreach ($unlockData as $unlockEntry) {
                        echo "<tr><td>";
                        if ($unlockEntry['ID'] == 0) {
                            echo "Ticket Created - ";
                            echo ($reportType == 1) ? "Triggered at wrong time" : "Doesn't Trigger";
                        } else {
                            echo GetAchievementAndTooltipDiv($unlockEntry['ID'], $unlockEntry['Title'], $unlockEntry['Description'],
                                $unlockEntry['Points'], $gameTitle, $unlockEntry['BadgeName'], true
                            );
                        }
                        echo "</td><td>";
                        $unlockDate = getNiceDate(strtotime($unlockEntry['Date']));
                        if ($unlockEntry['ID'] == $achID) {
                            echo "<b>$unlockDate</b>";
                        } else {
                            echo $unlockDate;
                        }
                        echo "</td><td>";
                        if ($unlockEntry['HardcoreMode'] == 1) {
                            if ($unlockEntry['ID'] == $achID) {
                                echo "<b>Hardcore</b>";
                            } else {
                                echo "Hardcore";
                            }
                        }
                        echo "</td></tr>";
                    }

                    echo "</table></div></div>";
                    echo "</td></tr>";
                }

                if ($user == $reportedBy || $permissions >= Permissions::Developer) {
                    echo "<tr>";

                    echo "<td>Action: </td><td colspan='7'>";
                    echo "<div class='smallicon'>";
                    echo "<span>";

                    echo "<b>Please, add some comments about the action you're going to take.</b><br>";
                    echo "<form method=post action='ticketmanager.php?i=$ticketID'>";
                    echo "<input type='hidden' name='i' value='$ticketID'>";

                    echo "<select name='action' required>";
                    echo "<option value='' disabled selected hidden>Choose an action...</option>";

                    if ($reportState == TicketState::Open || $reportState == TicketState::Request) {
                        if ($user == $reportedBy && $permissions < Permissions::Developer) {
                            echo "<option value='closed-mistaken'>Close - Mistaken report</option>";
                        } elseif ($permissions >= Permissions::Developer) {
                            echo "<option value='resolved'>Resolve as fixed (add comments about your fix below)</option>";
                            echo "<option value='demoted'>Demote achievement to Unofficial</option>";
                            echo "<option value='network'>Close - Network problems</option>";
                            echo "<option value='not-enough-info'>Close - Not enough information</option>";
                            echo "<option value='wrong-rom'>Close - Wrong ROM</option>";
                            echo "<option value='unable-to-reproduce'>Close - Unable to reproduce</option>";
                            echo "<option value='closed-mistaken'>Close - Mistaken report</option>";
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
                RenderCommentsComponent($user,
                    $numArticleComments,
                    $commentData,
                    $ticketID,
                    ArticleType::AchievementTicket,
                    $permissions
                );

                echo "</div>";
                echo "</td>";
                echo "</tr>";

                echo "</tbody></table>";
                echo "</div>";

                if ($permissions >= Permissions::Developer && getAchievementMetadata($achID, $dataOut)) {
                    getCodeNotes($gameID, $codeNotes);
                    $achMem = $dataOut['MemAddr'];
                    echo "<div class='devbox'>";
                    echo "<span onclick=\"$('#achievementlogic').toggle(); return false;\">Click to show achievement logic</span><br>";
                    echo "<div id='achievementlogic' style='display: none'>";

                    echo "<div style='clear:both;'></div>";
                    echo "<li> Achievement ID: " . $achID . "</li>";
                    echo "<div>";
                    echo "<li>Mem:</li>";
                    echo "<code>" . htmlspecialchars($achMem) . "</code>";
                    echo "<li>Mem explained:</li>";
                    echo "<code>" . getAchievementPatchReadableHTML($achMem, $codeNotes) . "</code>";
                    echo "</div>";

                    echo "</div>"; // achievementlogic
                    echo "</div>"; // devbox
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
