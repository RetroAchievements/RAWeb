<?php

// TODO migrate to AchievementTicketController::index() pages/tickets/manage.blade.php

use App\Community\Enums\ArticleType;
use App\Community\Enums\TicketAction;
use App\Community\Enums\TicketFilters;
use App\Community\Enums\TicketState;
use App\Community\Enums\TicketType;
use App\Enums\Permissions;
use App\Models\Ticket;
use App\Models\User;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Services\TriggerDecoderService;
use App\Models\Achievement;
use App\Models\PlayerAchievement;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;

if (!authenticateFromCookie($user, $permissions, $userDetails)) {
    abort(401);
}

$ticketID = requestInputSanitized('i', 0, 'integer');
if ($ticketID != 0) {
    echo view('pages.ticket.[ticket]')->with('ticket', Ticket::firstWhere('ID', $ticketID));
    return;
}

$maxCount = 50;
$count = requestInputSanitized('c', $maxCount, 'integer');
$offset = requestInputSanitized('o', 0, 'integer');

$defaultFilter = TicketFilters::Default;
$ticketFilters = requestInputSanitized('t', $defaultFilter, 'integer');

$altTicketData = null;
$commentData = null;
$filteredTicketsCount = null;
$ticketData = null;

$assignedToUser = null;
$reportedByUser = null;
$resolvedByUser = null;
$gamesTableFlag = 0;
$gameIDGiven = 0;
$achievementIDGiven = 0;
$achievementTitle = null;

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
        $achievementData = Achievement::find($achievementIDGiven);
        $achievementTitle = $achievementData['Title'];
        sanitize_outputs($achievementTitle);
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
        $gamesTableFlag === AchievementFlag::Unofficial
    );
}

if (!empty($gameIDGiven)) {
    $gameData = getGameData($gameIDGiven);
    $gameTitle = $gameData['Title'] ?? '';
    $consoleName = $gameData['ConsoleName'] ?? '';
    sanitize_outputs(
        $gameTitle,
        $consoleName,
    );
}

$pageTitle = "Ticket Manager";
?>
<x-app-layout :pageTitle="$pageTitle">
    <?php
    echo "<div class='navpath'>";
    if ($gamesTableFlag === 1) {
        echo "<a href='/ticketmanager.php'>$pageTitle</a></b> &raquo; <b>Games With Open Tickets";
    } else {
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
                ?>
                &raquo; <x-achievement.title :rawTitle="$achievementTitle" />
                <?php
            }
        }
    }
    echo "</div>";

    if ($gamesTableFlag == 1) {
        echo "<h3>Top " . (is_countable($ticketData) ? count($ticketData) : 0) . " Games Sorted By Most Outstanding Tickets</h3>";
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
        $openTicketsCount = countOpenTickets($gamesTableFlag === AchievementFlag::Unofficial);
        $filteredTicketsCount = countOpenTickets(
            $gamesTableFlag === AchievementFlag::Unofficial,
            $ticketFilters,
            $assignedToUser,
            $reportedByUser,
            $resolvedByUser,
            $gameIDGiven,
            $achievementIDGiven
        );
        echo "<h3>$pageTitle - " . localized_number($openTicketsCount) . " Open " . ($gamesTableFlag == AchievementFlag::Unofficial ? 'Unofficial ' : '') . " Ticket" . ($openTicketsCount == 1 ? "" : "s") . "</h3>";
    }

    echo "<div class='detaillist'>";

    if ($gamesTableFlag == 1) {
        echo "<p class='embedded'><b>If you're a developer and find games that you love in this list, consider helping to resolve their tickets.</b></p>";
        echo "<table class='table-highlight'><tbody>";

        echo "<tr class='do-not-highlight'>";
        echo "<th>Game</th>";
        echo "<th class='text-right'>Number of Open Tickets</th>";
        echo "</tr>";

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

            echo "<tr>";

            echo "<td class='py-2'>";
            ?>
                <x-game.multiline-avatar
                    :gameId="$nextTicket['GameID']"
                    :gameTitle="$nextTicket['GameTitle']"
                    :gameImageIcon="$nextTicket['GameIcon']"
                    :consoleName="$nextTicket['Console']"
                />
            <?php
            echo "</td>";

            echo "<td class='text-right'><a href='/ticketmanager.php?g=$gameID'>$openTickets</a></td>";

            echo "</tr>";
        }
        echo "</tbody></table>";
    } else {
            echo "<h4>Filters - " . localized_number($filteredTicketsCount) . " Ticket" . ($filteredTicketsCount == 1 ? "" : "s") . " Filtered</h4>";
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
                        $link .= Str::contains($link, '?') ? '&' : '?';
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
                        $ticketFilter |= TicketFilters::ResolvedByNonReporter;
                    }

                    return "<b><a href='" . $createLink('t', $ticketFilters & ~$ticketFilter) . "'>*$label</a></b>";
                }

                return "<a href='" . $createLink('t', $ticketFilters | $ticketFilter) . "'>$label</a>";
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
            echo $linkFilter('Other', TicketFilters::EmulatorOther) . ' | ';
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

            // Progression Filter
            echo "<div>";
            echo "<b>Achievement Type:</b> ";
            echo $linkFilter('Progression Only', TicketFilters::ProgressionOnly);
            echo "</div>";

            // Resolved By Filter
            if ($closedTickets || $resolvedTickets) {
                echo "<div>";
                echo "<b>Resolved By:</b> ";
                echo $linkFilter('Not Achievement Developer | ', TicketFilters::ResolvedByNonAuthor);
                echo $linkFilter('Not Achievement Reporter', TicketFilters::ResolvedByNonReporter);
                echo "</div>";
            }

            // Clear Filter
            if ($ticketFilters != $defaultFilter || $gamesTableFlag === AchievementFlag::Unofficial) {
                echo "<div>";
                echo "<a href='" . $createLink('t', $defaultFilter, 'f', 3) . "'>Clear Filter</a>";
                echo "</div>";
            }
            echo "</div>";

            $userFilter = function ($label, $param, $filteredUser) use ($createLink, $user) {
                if (isset($user) || !empty($filteredUser)) {
                    echo "<form class='form-inline' action='" . $createLink($param, null) . "'>";

                    echo "<p class='embedded'><b>$label:</b> ";
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
                    echo "<button class='btn'>Select</button>";
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

            echo "<table class='table-highlight'><tbody>";

            echo "<tr class='do-not-highlight'>";
            echo "<th>ID</th>";
            echo "<th>State</th>";
            echo "<th>Achievement</th>";
            echo "<th>Game</th>";
            echo "<th>Developer</th>";
            echo "<th>Reporter</th>";
            if ($closedTickets || $resolvedTickets) {
                echo "<th>Resolver</th>";
            }
            echo "<th class='whitespace-nowrap'>Reported At</th>";
            echo "</tr>";

            $rowCount = 0;

            foreach ($ticketData as $nextTicket) {
                $ticketID = $nextTicket['ID'];
                $achID = $nextTicket['AchievementID'];
                $achTitle = $nextTicket['AchievementTitle'];
                $achDesc = $nextTicket['AchievementDesc'];
                $achAuthor = $nextTicket['AchievementAuthor'];
                $achPoints = $nextTicket['Points'];
                $achType = $nextTicket['AchievementType'];
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
                    $achType,
                    $gameTitle,
                    $consoleName,
                    $reportNotes,
                    $reportedBy,
                    $resolvedBy
                );

                echo "<tr>";

                echo "<td>";
                echo "<a href='" . route('ticket.show', $ticketID) . "'>$ticketID</a>";
                echo "</td>";

                echo "<td>";
                echo TicketState::toString($reportState);
                echo "</td>";

                echo "<td style='min-width:25%'>";
                echo achievementAvatar(array_merge($nextTicket, ['type' => $achType]));
                echo "</td>";

                echo "<td>";
                ?>
                    <x-game.multiline-avatar
                        :gameId="$nextTicket['GameID']"
                        :gameTitle="$nextTicket['GameTitle']"
                        :gameImageIcon="$nextTicket['GameIcon']"
                        :consoleName="$nextTicket['ConsoleName']"
                    />
                <?php
                echo "</td>";

                echo "<td>";
                echo userAvatar($achAuthor);
                echo "</td>";
                echo "<td>";
                echo userAvatar($reportedBy);
                echo "</td>";
                if ($closedTickets || $resolvedTickets) {
                    echo "<td>";
                    echo userAvatar($resolvedBy);
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

            echo "<div class='text-right'>";
            $baseLink = $createLink(null, null);
            $baseLink .= (Str::contains($baseLink, '?') ? '&' : '?');
            RenderPaginator($filteredTicketsCount, $maxCount, $offset, "{$baseLink}o=");
            echo "</div>";
    }
    echo "</div>";
    ?>
</x-app-layout>
