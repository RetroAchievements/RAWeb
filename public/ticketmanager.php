<?php
require_once __DIR__ . '/../lib/bootstrap.php';

RA_ReadCookieCredentials( $user, $points, $truePoints, $unreadMessageCount, $permissions );

$maxCount = 100;
$count = seekGET( 'c', $maxCount );
$offset = seekGET( 'o', 0 );

$ticketID = seekGET( 'i', 0 );
settype( $ticketID, 'integer' );

$reportStates = array( "Closed", "Open", "Resolved" );
$ticketState = seekGET( 't', 1 );

if( $ticketID != 0 )
{
    $ticketData = getTicket( $ticketID );
    if( $ticketData == FALSE )
    {
        $ticketID = 0;
        $errorCode = 'notfound';
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

if( $ticketID == 0 )
{
    $assignedToUser = seekGET( 'u', NULL );
    $gameIDGiven = seekGET( 'g', NULL );
    $achievementIDGiven = seekGET( 'a', NULL );
    $ticketData = getAllTickets( $offset, $count, $assignedToUser, $gameIDGiven, $achievementIDGiven, $ticketState );
}
//var_dump( $ticketData );

$gameIDQuery = "";

if( $gameIDGiven != NULL )
{
    $gameIDQuery = "&g=$gameIDGiven";
}

$pageTitle = "Open Tickets";

if( $ticketID !== 0 )
    $pageTitle = "Inspect Ticket";

$errorCode = seekGET( 'e' );
RenderDocType();
?>

<head>
    <?php RenderSharedHeader( $user ); ?>
    <?php RenderTitleTag( $pageTitle, $user ); ?>
    <?php RenderGoogleTracking(); ?>
</head>

<body>
    <?php RenderTitleBar( $user, $points, $truePoints, $unreadMessageCount, $errorCode ); ?>
    <?php RenderToolbar( $user, $permissions ); ?>

    <div id="mainpage">
        <?php RenderErrorCodeWarning( 'left', $errorCode ); ?>
        <div class='left'>
            <?php
            echo "<div class='navpath'>";
            if( $ticketID == 0 )
            {
                echo "<b>";
                if( $ticketState == 0 )
                    echo "All ";
                if( $ticketState == 1 || $ticketState == 2 )
                    echo $reportStates[ $ticketState ];

                echo "Tickets</b>";
                if( !empty( $assignedToUser ) )
                    echo " &raquo; <a href='/User/$assignedToUser'>$assignedToUser</a>";
                if( !empty( $gameIDGiven ) )
                {
                    getGameTitleFromID( $gameIDGiven, $gameTitle, $consoleID, $consoleName, $forumTopic, $allData);
                    echo " &raquo; <a href='/Game/$gameIDGiven'>$gameTitle ($consoleName)</a>";
                }

            }
            else
            {
                echo "<a href='/ticketmanager.php'>Open Tickets</a>";
                echo " &raquo; <b>Ticket</b>";
            }
            echo "</div>";

            echo "<h3 class='longheader'>$pageTitle</h3>";

            echo "<div class='detaillist'>";

            if( $ticketID == 0 )
            {
                echo "Viewing: ";
                if( $assignedToUser !== $user && $ticketState == 0 )
                    echo "<b>All Tickets</b> | ";
                else
                    echo "<a href='/ticketmanager.php?t=0$gameIDQuery'>All Tickets</a> | ";

                if( $assignedToUser !== $user && $ticketState == 1 )
                    echo "<b>Open Tickets</b> | ";
                else
                    echo "<a href='/ticketmanager.php?t=1$gameIDQuery'>Open Tickets</a> | ";

                if( $assignedToUser == $user && $ticketState == 0 )
                    echo "<b>All My Tickets</b> | ";
                else
                    echo "<a href='/ticketmanager.php?t=0&u=$user$gameIDQuery'>All My Tickets</a> | ";

                if( $assignedToUser == $user && $ticketState == 1 )
                    echo "<b>My Open Tickets</b> | ";
                else
                    echo "<a href='/ticketmanager.php?t=1&u=$user$gameIDQuery'>My Open Tickets</a> | ";

                if( !empty( $gameIDGiven ) )
                {
                    echo "</br>Viewing Game ID: $gameIDGiven <a href='/ticketmanager.php?t=1&u=$user'>Clear Filter</a> ";
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
                    $tickState = $nextTicket[ 'ReportState' ];

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
                    echo $reportStates[ $tickState ];
                    echo "</td>";


                    echo "<td style='min-width:25%'>";
                    echo GetAchievementAndTooltipDiv( $achID, $achTitle, $achDesc, $achPoints, $gameTitle, $achBadgeName, TRUE );
                    echo "</td>";

                    echo "<td>";
                    echo GetGameAndTooltipDiv( $gameID, $gameTitle, $gameBadge, $consoleName );
                    echo "</td>";

                    echo "<td>";
                    echo GetUserAndTooltipDiv( $achAuthor, NULL, NULL, NULL, NULL, TRUE );
                    echo "</td>";

                    echo "<td>";
                    echo GetUserAndTooltipDiv( $reportedBy, NULL, NULL, NULL, NULL, TRUE );
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
                    echo "<a href='/ticketmanager.php?o=$prevOffset&amp;u=$assignedToUser&amp;t=$ticketState'>&lt; Previous $maxCount</a> - ";
                }
                if( $rowCount == $maxCount )
                {
                    //	Max number fetched, i.e. there are more. Can goto next $maxCount.
                    $nextOffset = $offset + $maxCount;
                    echo "<a href='/ticketmanager.php?o=$nextOffset&amp;u=$assignedToUser&amp;t=$ticketState'>Next $maxCount &gt;</a>";
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
                echo $reportStates[ $tickState ];
                echo "</td>";

                echo "<td style='min-width:25%'>";
                echo GetAchievementAndTooltipDiv( $achID, $achTitle, $achDesc, $achPoints, $gameTitle, $achBadgeName, TRUE );
                echo "</td>";

                echo "<td style='min-width:25%'>";
                echo GetGameAndTooltipDiv( $gameID, $gameTitle, $gameBadge, $consoleName, TRUE, 32 );
                echo "</td>";

                echo "<td>";
                echo GetUserAndTooltipDiv( $achAuthor, NULL, NULL, NULL, NULL, TRUE );
                echo "</td>";

                echo "<td>";
                echo GetUserAndTooltipDiv( $reportedBy, NULL, NULL, NULL, NULL, TRUE );
                echo "</td>";

                echo "<td>";
                echo $niceReportedAt;
                echo "</td>";

                echo "</tr>";

                echo "<tr>";
                echo "<td>";
                echo "Notes: ";
                echo "</td>";
                echo "<td colspan='4'>";
                echo "<code>$reportNotes</code>";
                echo "</td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td>";
                echo "Report Type: ";
                echo "</td>";
                echo "<td colspan='4'>";
                echo ( $reportType == 1 ) ? "<b>Triggered at wrong time</b>" : "<b>Doesn't Trigger</b>";
                echo "</td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td>";
                echo "State: ";
                echo "</td>";
                echo "<td colspan='4'>";
                echo ( $reportState == 1 ) ? "Unresolved" : ( ( $reportState == 2 ) ? "<b>Resolved: fixed</b>" : "<b>Resolved: will not fix</b>" );
                echo "</td>";
                echo "</tr>";

                if( $permissions >= \RA\Permissions::Developer )
                {
                    echo "<tr>";

                    echo "<td>";
                    echo "Developer:";
                    echo "</td>";

                    echo "<td colspan='4'>";
                    echo "<div class='smallicon'>";
                    echo "<span>";
                    $msgPayload = "Hi [user=$reportedBy], I'm contacting you about ticket www.retroachievements.org/ticketmanager.php?i=$ticketID ";
                    $msgPayload = rawurlencode( $msgPayload );
                    echo "<a href='createmessage.php?t=$reportedBy&amp;s=Bug%20Report%20($gameTitle)&amp;p=$msgPayload'>Contact $reportedBy</a>";
                    echo "</span>";
                    echo "</div>";
                    echo "</td>";

                    echo "</tr>";

                    if( $reportState == 1 )
                    {
                        echo "<tr>";

                        echo "<td></td><td colspan='4'>";
                        echo "<div class='smallicon'>";
                        echo "<span>";
                        echo "<a href='requestupdateticket.php?u=$user&amp;i=$ticketID&amp;v=2'>Resolve as fixed</a>";
                        echo "</span>";
                        echo "</div>";
                        echo "</td>";

                        echo "</tr>";

                        echo "<tr>";

                        echo "<td></td><td colspan='4'>";
                        echo "<div class='smallicon'>";
                        echo "<span>";
                        echo "<a href='requestupdateticket.php?u=$user&amp;i=$ticketID&amp;v=0'>Demote achievement to unofficial</a>";
                        //echo "<a href='/requestupdateachievement.php?a=$achID&amp;f=3&amp;u=$user&amp;v=5'>Demote achievement to unofficial</a>";
                        echo "</span>";
                        echo "</div>";
                        echo "</td>";

                        echo "</tr>";
                    }
                }

                echo "<tr>";
                echo "<td></td><td colspan='4'>";
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
                        echo "<td></td><td colspan='4'>";
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
                        echo "<td></td><td colspan='4'>";
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
                    echo "<td></td><td colspan='4'>";
                    echo "<div class='temp'>";
                    echo "No other tickets found for this achievement";
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
            }
            echo "</div>";
            ?>
            <br/>
        </div>
    </div>

    <?php RenderFooter(); ?>

</body>
</html>
