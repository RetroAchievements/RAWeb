<?php
require_once __DIR__ . '/../lib/bootstrap.php';

RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );

$maxCount = 100;
$count = seekGET( 'c', $maxCount );
$offset = seekGET( 'o', 0 );

$ticketID = seekPOSTorGET( 'i', 0 );
settype( $ticketID, 'integer' );

$reportStates = ["Closed", "Open", "Resolved"];
$ticketState = seekGET( 't', 1 );

if( $ticketID != 0 )
{
    $ticketData = getTicket( $ticketID );
    if( $ticketData == FALSE )
    {
        $ticketID = 0;
        $errorCode = 'notfound';
    }

    $action = seekPOSTorGET( 'action', NULL );
    $reason = NULL;
    switch( $action )
    {
        case "closed-mistaken":
            $ticketState = 0;
            $reason = "Mistaken report";
            break;

        case "resolved":
            if( $permissions >= \RA\Permissions::Developer )
                $ticketState = 2;
            break;

        case "demoted":
            if( $permissions >= \RA\Permissions::Developer )
            {
                $ticketState = 0;
                $reason = "Demoted";
            }
            break;

        case "not-enough-info":
            if( $permissions >= \RA\Permissions::Developer )
            {
                $ticketState = 0;
                $reason = "Not enough information";
            }
            break;

        case "wrong-rom":
            if( $permissions >= \RA\Permissions::Developer )
            {
                $ticketState = 0;
                $reason = "Wrong ROM";
            }
            break;

        case "network":
            if( $permissions >= \RA\Permissions::Developer )
            {
                $ticketState = 0;
                $reason = "Network problems";
            }
            break;

        case "closed-other":
            if( $permissions >= \RA\Permissions::Developer )
            {
                $ticketState = 0;
                $reason = "See the comments";
            }
            break;

        case "reopen":
            $ticketState = 1;
            break;

        default:
            $action = NULL;
            break;
    }

    if( $action != NULL &&
        $ticketState != $ticketData[ 'ReportState' ] &&
        (   $permissions >= \RA\Permissions::Developer ||
            $user == $ticketData[ 'ReportedBy' ]
        )
    )
    {
            updateTicket( $user, $ticketID, $ticketState, $reason );
            $ticketData = getTicket( $ticketID );
    }

    $numArticleComments = getArticleComments( 7, $ticketID, 0, 20, $commentData );

    $altTicketData = getAllTickets( 0, 99, NULL, NULL, $ticketData[ 'AchievementID' ], 0 );
    //var_dump($altTicketData);
    $numOpenTickets = 0;
    foreach( $altTicketData as $pastTicket )
    {
        settype( $pastTicket[ "ID" ], 'integer' );

        if( $pastTicket[ "ReportState" ] == 1 && $pastTicket[ "ID" ] !== $ticketID )
            $numOpenTickets++;
    }

    $numClosedTickets = ( count( $altTicketData ) - $numOpenTickets ) - 1;
}

$gamesTableFlag = 0;
$gameIDGiven = 0;
if( $ticketID == 0 )
{
    $gamesTableFlag = seekGET( 'f' );
    if( $gamesTableFlag == 1 )
    {
        $count = seekGET( 'c', 50 );
        $ticketData = gamesSortedByOpenTickets( $count );
    }
    else
    {
        $assignedToUser = seekGET( 'u', NULL );
        if( !isValidUsername( $assignedToUser ) )
            $assignedToUser = NULL;
        $gameIDGiven = seekGET( 'g', NULL );

        $achievementIDGiven = seekGET( 'a', NULL );
        if( $achievementIDGiven > 0 )
        {
            $achievementData = GetAchievementData( $achievementIDGiven);
            $achievementTitle = $achievementData[ 'Title' ];
            $gameIDGiven = $achievementData[ 'GameID' ]; // overwrite the given game ID
        }

        if( $gamesTableFlag == 5 )
            $ticketData = getAllTickets( $offset, $count, $assignedToUser, $gameIDGiven, $achievementIDGiven, $ticketState, TRUE );
        else
            $ticketData = getAllTickets( $offset, $count, $assignedToUser, $gameIDGiven, $achievementIDGiven, $ticketState );
    }
}

if( !empty( $gameIDGiven ) )
    getGameTitleFromID( $gameIDGiven, $gameTitle, $consoleID, $consoleName, $forumTopic, $gameData);

$pageTitle = "Open Tickets";

if( $ticketID !== 0 )
    $pageTitle = "Inspect Ticket";

$errorCode = seekGET( 'e' );
RenderDocType();
?>

<head>
    <?php
    RenderSharedHeader( $user );

    if( !empty( $gameIDGiven ) )
        RenderFBMetaData( $pageTitle, "tickets", "/Badge/". $gameData[ 'ImageIcon' ] .".png", "/Game/$gameIDGiven", "Tickets for $gameTitle ($consoleName)" );
    else if( $ticketID !== 0 && $ticketData )
        RenderFBMetaData( $pageTitle, "tickets", "/Badge/". $ticketData[ 'BadgeName' ] .".png", "/achievement/". $ticketData[ 'AchievementID' ], "Tickets for '". $ticketData[ 'AchievementTitle' ] ."' - ". $ticketData[ 'GameTitle' ] ." (". $ticketData[ 'ConsoleName' ] .")" );

    RenderTitleTag( $pageTitle, $user );
    RenderGoogleTracking();
    ?>
</head>

<body>
    <?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode, $permissions ); ?>
    <?php RenderToolbar( $user, $permissions ); ?>

    <div id="mainpage">
        <?php RenderErrorCodeWarning( 'left', $errorCode ); ?>
        <div class='left'>
            <?php
            echo "<div class='navpath'>";
            if( $gamesTableFlag == 1 )
            {
                echo "<b><a href='/ticketmanager.php'>Open Tickets</a></b> &raquo; <b>Games With Open Tickets</b>";
            }
            else if( $ticketID == 0 )
            {
                echo "<b>";
                if( $ticketState == 0 )
                    echo "All ";
                if( $ticketState == 1 || $ticketState == 2 )
                    echo $reportStates[ $ticketState ] . " ";

                echo "Tickets</b>";
                if( !empty( $assignedToUser ) )
                    echo " &raquo; <a href='/User/$assignedToUser'>$assignedToUser</a>";
                if( !empty( $gameIDGiven ) )
                {
                    echo " &raquo; <a href='/ticketmanager.php?g=$gameIDGiven'>$gameTitle ($consoleName)</a>";
                    if( !empty( $achievementIDGiven ) )
                        echo " &raquo; $achievementTitle";
                }

            }
            else
            {
                echo "<a href='/ticketmanager.php'>Open Tickets</a>";
                echo " &raquo; <b>Ticket</b>";
            }
            echo "</div>";

            if( $gamesTableFlag == 1 )
                echo "<h3>Top " . count( $ticketData ) . " Games Sorted By Most Outstanding Tickets</h3>";
            else
                echo "<h3 class='longheader'>$pageTitle</h3>";

            echo "<div class='detaillist'>";

            if( $gamesTableFlag == 1 )
            {
                echo "<p><b>If you're a developer and find games that you love in this list, consider helping to resolve their tickets.</b></p>";
                echo "<table><tbody>";

                echo "<th>Game</th>";
                echo "<th>Number of Open Tickets</th>";

                $rowCount = 0;

                foreach( $ticketData as $nextTicket )
                {
                    $gameID = $nextTicket[ 'GameID' ];
                    $gameTitle = $nextTicket[ 'GameTitle' ];
                    $gameBadge = $nextTicket[ 'GameIcon' ];
                    $consoleName = $nextTicket[ 'Console' ];
                    $gameTitle = $nextTicket[ 'GameTitle' ];
                    $openTickets = $nextTicket[ 'OpenTickets' ];

                    if( $rowCount++ % 2 == 0 )
                        echo "<tr>";
                    else
                        echo "<tr class='alt'>";

                    echo "<td>";
                    echo GetGameAndTooltipDiv( $gameID, $gameTitle, $gameBadge, $consoleName );
                    echo "</td>";
                    echo "<td><a href='/ticketmanager.php?t=1&g=$gameID'>$openTickets</a></td>";

                    echo "</tr>";
                }
                echo "</tbody></table>";
            }
            else if( $ticketID == 0 )
            {
                echo "<h4>Filters</h4>";

                echo "<p><b>Ticket State:</b> ";
                if( $ticketState == 0 )
                    echo "<b>All Tickets</b> | ";
                else
                    echo "<a href='/ticketmanager.php?t=0&g=$gameIDGiven&u=$assignedToUser'>All Tickets</a> | ";

                if( $ticketState == 1 && $gamesTableFlag != 5 )
                {
                    $openTicketsCount = countOpenTickets();
                    echo "<b>Open Tickets (". $openTicketsCount .")</b> | ";
                }
                else
                    echo "<a href='/ticketmanager.php?t=1&g=$gameIDGiven&u=$assignedToUser'>Open Tickets</a> | ";

                if( $ticketState == 2 )
                    echo "<b>Resolved Tickets</b>";
                else
                    echo "<a href='/ticketmanager.php?t=2&g=$gameIDGiven&u=$assignedToUser'>Resolved Tickets</a>";

                if( $gamesTableFlag == 5 )
                    echo " | <b>Open Tickets for Unofficial (". countOpenTickets( TRUE ) .")</b>";

                echo "</p>";

                if( isset( $user ) || !empty( $assignedToUser ) )
                {
                    echo "<p><b>Developer:</b> ";
                    if( isset( $user ) )
                    {
                        if( $assignedToUser == $user )
                            echo "<b>$user</b> | ";
                        else
                            echo "<a href='/ticketmanager.php?t=$ticketState&u=$user&g=$gameIDGiven'>$user</a> | ";
                    }

                    if( !empty( $assignedToUser ) && $assignedToUser !== $user )
                            echo "<b>$assignedToUser</b> | ";

                    if( !empty( $assignedToUser ) )
                        echo "<a href='/ticketmanager.php?t=$ticketState&g=$gameIDGiven'>Clear Filter</a>";
                    else
                        echo "<b>Clear Filter</b>";
                    echo "</p>";
                }

                if( !empty( $gameIDGiven ) )
                {
                    echo "<p><b>Game</b>";
                    if( !empty( $achievementIDGiven ) )
                    {
                        echo "<b>/Achievement</b>: ";
                        echo "<a href='/ticketmanager.php?g=$gameIDGiven'>$gameTitle ($consoleName)</a>";
                        echo " | <b>$achievementTitle</b>";
                    }
                    else
                        echo ": <b>$gameTitle ($consoleName)</b>";
                    echo " | <a href='/ticketmanager.php?t=$ticketState&u=$assignedToUser'>Clear Filter</a></p>";
                }

                echo "<table><tbody>";

                echo "<th>ID</th>";
                echo "<th>Status</th>";
                echo "<th>Achievement</th>";
                echo "<th>Game</th>";
                echo "<th>Developer</th>";
                echo "<th>Reporter</th>";
                echo "<th>Reported At</th>";

                $rowCount = 0;

                foreach( $ticketData as $nextTicket )
                {
                    //var_dump( $nextTicket );
                    //$query = "SELECT ach.ID, ach.Title AS AchievementTitle, ach.Description, ach.Points, ach.Author, ach.DateCreated, ach.DateModified, ach.BadgeName, ach.GameID, gd.Title AS GameTitle, gd.ConsoleID, c.Name AS ConsoleName ";

                    $ticketID = $nextTicket[ 'ID' ];
                    $achID = $nextTicket[ 'AchievementID' ];
                    $achTitle = $nextTicket[ 'AchievementTitle' ];
                    $achDesc = $nextTicket[ 'AchievementDesc' ];
                    $achAuthor = $nextTicket[ 'AchievementAuthor' ];
                    $achPoints = $nextTicket[ 'Points' ];
                    $achBadgeName = $nextTicket[ 'BadgeName' ];
                    $gameID = $nextTicket[ 'GameID' ];
                    $gameTitle = $nextTicket[ 'GameTitle' ];
                    $gameBadge = $nextTicket[ 'GameIcon' ];
                    $consoleName = $nextTicket[ 'ConsoleName' ];
                    $reportType = $nextTicket[ 'ReportType' ];
                    $reportNotes = $nextTicket[ 'ReportNotes' ];
                    $reportState = $nextTicket[ 'ReportState' ];

                    $reportedAt = $nextTicket[ 'ReportedAt' ];
                    $niceReportedAt = getNiceDate( strtotime( $reportedAt ) );
                    $reportedBy = $nextTicket[ 'ReportedBy' ];
                    $resolvedAt = $nextTicket[ 'ResolvedAt' ];
                    $niceResolvedAt = getNiceDate( strtotime( $resolvedAt ) );
                    $resolvedBy = $nextTicket[ 'ResolvedBy' ];

                    if( $rowCount++ % 2 == 0 )
                        echo "<tr>";
                    else
                        echo "<tr class='alt'>";

                    echo "<td>";
                    echo "<a href='/ticketmanager.php?i=$ticketID'>$ticketID</a>";
                    echo "</td>";

                    echo "<td>";
                    echo $reportStates[ $reportState ];
                    echo "</td>";


                    echo "<td style='min-width:25%'>";
                    echo GetAchievementAndTooltipDiv( $achID, $achTitle, $achDesc, $achPoints, $gameTitle, $achBadgeName, TRUE );
                    echo "</td>";

                    echo "<td>";
                    echo GetGameAndTooltipDiv( $gameID, $gameTitle, $gameBadge, $consoleName );
                    echo "</td>";

                    echo "<td>";
                    echo GetUserAndTooltipDiv( $achAuthor, NULL, NULL, NULL, NULL, TRUE );
                    echo "<a href='/User/$achAuthor'>$achAuthor</a>";
                    echo "</td>";

                    echo "<td>";
                    echo GetUserAndTooltipDiv( $reportedBy, NULL, NULL, NULL, NULL, TRUE );
                    echo "<a href='/User/$reportedBy'>$reportedBy</a>";
                    echo "</td>";

                    echo "<td>";
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
                if( $offset > 0 )
                {
                    $prevOffset = $offset - $maxCount;
                    echo "<a href='/ticketmanager.php?u=$assignedToUser&amp;t=$ticketState'>First</a> - ";
                    echo "<a href='/ticketmanager.php?o=$prevOffset&amp;u=$assignedToUser&amp;t=$ticketState'>&lt; Previous $maxCount</a> - ";
                }
                if( $rowCount == $maxCount )
                {
                    //	Max number fetched, i.e. there are more. Can goto next $maxCount.
                    $nextOffset = $offset + $maxCount;
                    echo "<a href='/ticketmanager.php?o=$nextOffset&amp;u=$assignedToUser&amp;t=$ticketState'>Next $maxCount &gt;</a>";
                    if( $ticketState == 1 && $gamesTableFlag != 5 )
                        echo " - <a href='/ticketmanager.php?o=". ($openTicketsCount - 99) ."&amp;u=$assignedToUser&amp;t=$ticketState'>Last</a>";
                }
                echo "</div>";
            }
            else
            {
                $nextTicket = $ticketData;
                $ticketID = $nextTicket[ 'ID' ];
                $achID = $nextTicket[ 'AchievementID' ];
                $achTitle = $nextTicket[ 'AchievementTitle' ];
                $achDesc = $nextTicket[ 'AchievementDesc' ];
                $achAuthor = $nextTicket[ 'AchievementAuthor' ];
                $achPoints = $nextTicket[ 'Points' ];
                $achBadgeName = $nextTicket[ 'BadgeName' ];
                $gameID = $nextTicket[ 'GameID' ];
                $gameTitle = $nextTicket[ 'GameTitle' ];
                $gameBadge = $nextTicket[ 'GameIcon' ];
                $consoleName = $nextTicket[ 'ConsoleName' ];
                $reportState = $nextTicket[ 'ReportState' ];
                $reportType = $nextTicket[ 'ReportType' ];
                $reportNotes = $nextTicket[ 'ReportNotes' ];

                $reportedAt = $nextTicket[ 'ReportedAt' ];
                $niceReportedAt = getNiceDate( strtotime( $reportedAt ) );
                $reportedBy = $nextTicket[ 'ReportedBy' ];
                $resolvedAt = $nextTicket[ 'ResolvedAt' ];
                $niceResolvedAt = getNiceDate( strtotime( $resolvedAt ) );
                $resolvedBy = $nextTicket[ 'ResolvedBy' ];

                echo "<table><tbody>";

                echo "<th>ID</th>";
                echo "<th>Status</th>";
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
                echo $reportStates[ $reportState ];
                echo "</td>";

                echo "<td style='min-width:25%'>";
                echo GetAchievementAndTooltipDiv( $achID, $achTitle, $achDesc, $achPoints, $gameTitle, $achBadgeName, TRUE );
                echo "</td>";

                echo "<td style='min-width:25%'>";
                echo GetGameAndTooltipDiv( $gameID, $gameTitle, $gameBadge, $consoleName );
                echo "</td>";

                echo "<td>";
                echo GetUserAndTooltipDiv( $achAuthor, NULL, NULL, NULL, NULL, TRUE );
                echo "<a href='/User/$achAuthor'>$achAuthor</a>";
                echo "</td>";

                echo "<td>";
                echo GetUserAndTooltipDiv( $reportedBy, NULL, NULL, NULL, NULL, TRUE );
                echo "<a href='/User/$reportedBy'>$reportedBy</a>";
                echo "</td>";

                echo "<td>";
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
                echo ( $reportType == 1 ) ? "<b>Triggered at wrong time</b>" : "<b>Doesn't Trigger</b>";
                echo "</td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td></td><td colspan='6'>";
                echo "<div class='temp'>";
                echo "<a href='ticketmanager.php?g=$gameID&t=1'>View other tickets for this game</a>";
                echo "</div>";
                echo "</td>";
                echo "</tr>";

                if( $numOpenTickets > 0 || $numClosedTickets > 0 )
                {
                    if( $numOpenTickets > 0 )
                    {
                        echo "<tr>";
                        echo "<td></td><td colspan='6'>";
                        echo "Found $numOpenTickets other open tickets for this achievement: ";

                        foreach( $altTicketData as $nextTicket )
                        {
                            $nextTicketID = $nextTicket[ 'ID' ];
                            settype( $nextTicketID, 'integer' );
                            settype( $ticketID, 'integer' );

                            if( $nextTicketID !== $ticketID && ( $nextTicket[ 'ReportState' ] == 1 ) )
                            {
                                echo "<a href='ticketmanager.php?i=$nextTicketID'>$nextTicketID</a>, ";
                            }
                        }

                        echo "</td>";
                        echo "</tr>";
                    }
                    if( $numClosedTickets > 0 )
                    {
                        echo "<tr>";
                        echo "<td></td><td colspan='6'>";
                        echo "Found $numClosedTickets closed tickets for this achievement: ";

                        foreach( $altTicketData as $nextTicket )
                        {
                            $nextTicketID = $nextTicket[ 'ID' ];
                            settype( $nextTicketID, 'integer' );
                            settype( $ticketID, 'integer' );
                            settype( $nextTicket[ 'ReportState' ], 'integer' );

                            if( $nextTicketID !== $ticketID && ( $nextTicket[ 'ReportState' ] !== 1 ) )
                            {
                                echo "<a href='ticketmanager.php?i=$nextTicketID'>$nextTicketID</a>, ";
                            }
                        }

                        echo "</td>";
                        echo "</tr>";
                    }
                }
                else
                {
                    echo "<tr>";
                    echo "<td></td><td colspan='6'>";
                    echo "<div class='temp'>";
                    echo "No other tickets found for this achievement";
                    echo "</div>";
                    echo "</td>";
                    echo "</tr>";
                }

                if( $permissions >= \RA\Permissions::Developer )
                {
                    echo "<tr>";

                    echo "<td>Reporter:</td>";
                    echo "<td colspan='6'>";
                    echo "<div class='smallicon'>";
                    echo "<span>";
                    $msgPayload = "Hi [user=$reportedBy], I'm contacting you about ticket retroachievements.org/ticketmanager.php?i=$ticketID ";
                    $msgPayload = rawurlencode( $msgPayload );
                    echo "<a href='createmessage.php?t=$reportedBy&amp;s=Bug%20Report%20($gameTitle)&amp;p=$msgPayload'>Contact the reporter - $reportedBy</a>";
                    echo "</span>";
                    echo "</div>";
                    echo "</td>";
                    echo "</tr>";
                }

                echo "<tr>";
                echo "<td></td><td colspan='6'>";

                if( getUserUnlockAchievement( $reportedBy, $achID, $unlockData ) )
                {
                    echo "$reportedBy earned this achievement at ". getNiceDate( strtotime( $unlockData[0][ 'Date' ] ) );
                    if( $unlockData[0][ 'Date' ] >= $reportedAt )
                        echo " (after the report).";
                    else
                        echo " (before the report).";
                }
                else
                    echo "$reportedBy did not earn this achievement.";

                if( $user == $reportedBy || $permissions >= \RA\Permissions::Developer )
                {
                    echo "<tr>";

                    echo "<td>Action: </td><td colspan='6'>";
                    echo "<div class='smallicon'>";
                    echo "<span>";

                    echo "<b>Please, add some comments about the action you're going to take.</b><br>";
                    echo "<form method=post action='ticketmanager.php?i=$ticketID'>";
                    echo "<input type='hidden' name='i' value='$ticketID'>";

                    echo "<select name='action' required>";
                    echo "<option value='' disabled selected hidden>Choose an action...</option>";
                    if( $reportState == 1 )
                    {
                        if( $user == $reportedBy ) // only the reporter can close as a mistaken report
                            echo "<option value='closed-mistaken'>Close - Mistaken report</option>";

                        if( $permissions >= \RA\Permissions::Developer )
                        {
                            echo "<option value='resolved'>Resolve as fixed (add comments about your fix below)</option>";
                            echo "<option value='demoted'>Demote achievement to Unofficial</option>";
                            echo "<option value='network'>Close - Network problems</option>";
                            echo "<option value='not-enough-info'>Close - Not enough information</option>";
                            echo "<option value='wrong-rom'>Close - Wrong ROM</option>";
                            echo "<option value='closed-other'>Close - Another reason (add comments below)</option>";
                        }
                    }
                    else // ticket is not open
                        echo "<option value='reopen'>Reopen this ticket</option>";

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
                RenderCommentsComponent( $user, $numArticleComments, $commentData, $ticketID, 7, $forceAllowDeleteComments );

                echo "</div>";
                echo "</td>";
                echo "</tr>";

                echo "</tbody></table>";
                echo "</div>";

                if( $permissions >= \RA\Permissions::Developer && getAchievementMetadata( $achID, $dataOut ) )
                {
                    getCodeNotes( $gameID, $codeNotes );
                    $achMem = $dataOut[ 'MemAddr' ];
                    echo "<div class='devbox'>";
                    echo "<span onclick=\"$('#devboxcontent').toggle(500); return false;\">Click to show achievement logic:</span><br/>";
                    echo "<div id='devboxcontent'>";

                    echo "<div style='clear:both;'></div>";
                    echo "<li> Achievement ID: ". $achID ."</li>";
                    echo "<div>";
                    echo "<li>Mem:</li>";
                    echo "<code>". htmlspecialchars( $achMem ) ."</code>";
                    echo "<li>Mem explained:</li>";
                    echo "<code>" . getAchievementPatchReadableHTML( $achMem, $codeNotes ) . "</code>";
                    echo "</div>";

                    echo "</div>"; //   devboxcontent
                    echo "</div>"; //   devbox
                }

            }
            echo "</div>";
            ?>
            <br/>
        </div>
    </div>

    <?php RenderFooter(); ?>

</body>
</html>
