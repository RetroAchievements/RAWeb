<?php
require_once( __DIR__ . '/../bootstrap.php' );
function SubmitNewTicketsJSON( $userSubmitter, $idsCSV, $reportType, $noteIn, $ROMMD5 )
{
    global $db;

    $note = mysqli_real_escape_string( $db, $noteIn );
    $note .= "<br/>MD5: $ROMMD5";
    //error_log( "mysqli_real_escape_string turned #$noteIn# into #$note#" );

    $submitterUserID = getUserIDFromUser( $userSubmitter );
    settype( $reportType, 'integer' );

    $achievementIDs = explode( ',', $idsCSV );

    $errorsEncountered = false;

    $idsFound = 0;
    $idsAdded = 0;

    foreach( $achievementIDs as $achID )
    {
        settype( $achID, 'integer' );
        if( $achID == 0 )
            continue;

        $idsFound++;

        $query = "INSERT INTO Ticket( AchievementID, ReportedByUserID, ReportType, ReportNotes, ReportedAt, ResolvedAt, ResolvedByUserID ) VALUES( $achID, $submitterUserID, $reportType, \"$note\", NOW(), NULL, NULL )";
        log_sql( $query );

        $dbResult = mysqli_query( $db, $query ); //	Unescaped?
        $ticketID = mysqli_insert_id( $db );
        error_log( __FUNCTION__ . " produced insert id of $ticketID " );

        if( $dbResult == FALSE )
        {
            $errorsEncountered = true;
            error_log( $query );
            error_log( __FUNCTION__ . " failed?! $userSubmitter, $achID, $reportType, $note" );
        }
        else
        {
            //	Success
            if( GetAchievementMetadata( $achID, $achData ) )
            {
                $achAuthor = $achData[ 'Author' ];
                $achTitle = $achData[ 'AchievementTitle' ];
                $gameID = $achData[ 'GameID' ];
                $gameTitle = $achData[ 'GameTitle' ];

                $problemTypeStr = ( $reportType == 1 ) ? "Triggers at wrong time" : "Doesn't trigger";

                $bugReportMessage = "Hi, $achAuthor!
[user=$userSubmitter] would like to report a bug with an achievement you've created:
Achievement: [ach=$achID] ($achTitle)
Game: [game=$gameID] ($gameTitle)
Problem: $problemTypeStr
Comment: $note

This ticket will be raised and will be available for all developers to inspect and manage at the following URL:
http://retroachievements.org/ticketmanager.php?i=$ticketID

Thanks!";
                CreateNewMessage( $userSubmitter, $achData[ 'Author' ], "Bug Report ($gameTitle)", $bugReportMessage );
            }

            $idsAdded++;
        }
    }

    $returnMsg = array();
    $returnMsg[ 'Detected' ] = $idsFound;
    $returnMsg[ 'Added' ] = $idsAdded;
    $returnMsg[ 'Success' ] = ( $errorsEncountered == FALSE );

    return $returnMsg;
}

function SubmitNewTickets( $userSubmitter, $idsCSV, $reportType, $noteIn, &$summaryMsgOut )
{
    global $db;
    $note = mysqli_real_escape_string( $db, $noteIn );

    error_log( "mysqli_real_escape_string turned #$noteIn# into #$note#" );

    $submitterUserID = getUserIDFromUser( $userSubmitter );
    settype( $reportType, 'integer' );

    $achievementIDs = explode( ',', $idsCSV );

    $errorsEncountered = false;

    $idsFound = 0;
    $idsAdded = 0;

    foreach( $achievementIDs as $achID )
    {
        settype( $achID, 'integer' );
        if( $achID == 0 )
            continue;

        $idsFound++;

        $query = "INSERT INTO Ticket( AchievementID, ReportedByUserID, ReportType, ReportNotes, ReportedAt, ResolvedAt, ResolvedByUserID ) VALUES( $achID, $submitterUserID, $reportType, \"$note\", NOW(), NULL, NULL )";
        log_sql( $query );

        $dbResult = mysqli_query( $db, $query ); //	Unescaped?
        $ticketID = mysqli_insert_id( $db );
        error_log( __FUNCTION__ . " produced insert id of $ticketID " );

        if( $dbResult == FALSE )
        {
            $errorsEncountered = true;
            error_log( $query );
            error_log( __FUNCTION__ . " failed?! $userSubmitter, $achID, $reportType, $note" );
        }
        else
        {
            //	Success
            if( GetAchievementMetadata( $achID, $achData ) )
            {
                $achAuthor = $achData[ 'Author' ];
                $gameID = $achData[ 'GameID' ];
                $gameTitle = $achData[ 'GameTitle' ];

                $problemTypeStr = ( $reportType == 1 ) ? "Triggers at wrong time" : "Doesn't trigger";

                $bugReportMessage = "Hi, $achAuthor!\r\n
[user=$userSubmitter] would like to report a bug with an achievement you've created:
Achievement: [ach=$achID]
Game: [game=$gameID]
Problem: $problemTypeStr
Comment: $note

This ticket will be raised and will be available for all developers to inspect and manage at the following URL:
http://retroachievements.org/ticketmanager.php?i=$ticketID

Thanks!";
                CreateNewMessage( $userSubmitter, $achData[ 'Author' ], "Bug Report ($gameTitle)", $bugReportMessage );
            }

            $idsAdded++;
        }
    }

    if( $idsAdded > 0 && $idsFound == $idsAdded )
    {
        //	Normal exit
        $summaryMsgOut = "OK:";
    }
    else if( $idsAdded > 0 )
    {
        $summaryMsgOut = "OK:$idsAdded/$idsFound added.";
    }
    else
    {
        $summaryMsgOut = "FAILED!";
    }

    return ( $errorsEncountered == FALSE );
}

function getAllTickets( $offset = 0, $limit = 50, $assignedToUser = NULL, $givenGameID = NULL, $givenAchievementID = NULL, $ticketState = 1 )
{
    global $db;

    $retVal = array();
    settype( $givenGameID, 'integer' );
    settype( $ticketState, 'integer' );
    settype( $givenAchievementID, 'integer' );

    $innerCond = "TRUE";
    if( !empty( $assignedToUser ) )
    {
        $assignedToUser = mysqli_real_escape_string( $db, $assignedToUser );
        $innerCond .= " AND ach.Author = '$assignedToUser'";
    }
    if( $givenGameID != 0 )
    {
        $innerCond .= " AND gd.ID = $givenGameID";
    }
    if( $ticketState != 0 )
    {
        $innerCond .= " AND tick.ReportState = $ticketState";
    }
    if( $givenAchievementID != 0 )
    {
        $innerCond .= " AND tick.AchievementID = $givenAchievementID";
    }


    $query = "SELECT tick.ID, tick.AchievementID, ach.Title AS AchievementTitle, ach.Description AS AchievementDesc, ach.Points, ach.BadgeName,
				ach.Author AS AchievementAuthor, ach.GameID, c.Name AS ConsoleName, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon,
				tick.ReportedAt, tick.ReportType, tick.ReportNotes, ua.User AS ReportedBy, tick.ResolvedAt, ua2.User AS ResolvedBy, tick.ReportState
			  FROM Ticket AS tick
			  LEFT JOIN Achievements AS ach ON ach.ID = tick.AchievementID
			  LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
			  LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
			  LEFT JOIN UserAccounts AS ua ON ua.ID = tick.ReportedByUserID
			  LEFT JOIN UserAccounts AS ua2 ON ua2.ID = tick.ResolvedByUserID
			  WHERE $innerCond
			  ORDER BY tick.ID DESC
			  LIMIT $offset, $limit";

    //echo $query;

    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        while( $nextData = mysqli_fetch_assoc( $dbResult ) )
            $retVal[] = $nextData;
    }
    else
    {
        error_log( __FUNCTION__ . " failed?! $offset, $limit" );
    }

    return $retVal;
}

function getTicket( $ticketID )
{
    $query = "SELECT tick.ID, tick.AchievementID, ach.Title AS AchievementTitle, ach.Description AS AchievementDesc, ach.Points, ach.BadgeName,
				ach.Author AS AchievementAuthor, ach.GameID, c.Name AS ConsoleName, gd.Title AS GameTitle, gd.ImageIcon AS GameIcon,
				tick.ReportedAt, tick.ReportType, tick.ReportState, tick.ReportNotes, ua.User AS ReportedBy, tick.ResolvedAt, ua2.User AS ResolvedBy
			  FROM Ticket AS tick
			  LEFT JOIN Achievements AS ach ON ach.ID = tick.AchievementID
			  LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
			  LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
			  LEFT JOIN UserAccounts AS ua ON ua.ID = tick.ReportedByUserID
			  LEFT JOIN UserAccounts AS ua2 ON ua2.ID = tick.ResolvedByUserID
			  WHERE tick.ID = $ticketID
			  ";

    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        return mysqli_fetch_assoc( $dbResult );
    }
    else
    {
        error_log( __FUNCTION__ . " failed?! $offset, $limit" );
        return FALSE;
    }
}

function updateTicket( $user, $ticketID, $ticketVal )
{
    $userID = getUserIDFromUser( $user );
    $query = "UPDATE Ticket
			  SET ReportState=$ticketVal, ResolvedAt=NOW(), ResolvedByUserID=$userID
			  WHERE ID=$ticketID";

    log_sql( $query );

    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        $ticketData = getTicket( $ticketID );
        $userReporter = $ticketData[ 'ReportedBy' ];
        $achID = $ticketData[ 'AchievementID' ];
        $achTitle = $ticketData[ 'AchievementTitle' ];
        $gameTitle = $ticketData[ 'GameTitle' ];
        $consoleName = $ticketData[ 'ConsoleName' ];

        if( $ticketVal == 0 )
        {
            //	Resolution was to please demote:
            updateAchievementFlags( $achID, 5 );
        }

        $resolution = ($ticketVal == 2) ? "fixed" : "removed";

        addArticleComment( "Server", 7, $ticketID, "Resolved as $resolution by $user" );

        getAccountDetails( $userReporter, $reporterData );
        $email = $reporterData[ 'EmailAddress' ];

        $emailTitle = "Ticket status changed";
        $link = "<a href='http://retroachievements.org/ticketmanager.php?i=$ticketID'>here</a>";

        $msg = "Hello $userReporter!<br/>" .
                "<br/>" .
                "$achTitle - $gameTitle ($consoleName)</br>" .
                "<br/>" .
                "The above achievement you reported as broken has been marked '$resolution' by $user.</br>" .
                "<br/>" .
                "Click $link to view the ticket<br/>" .
                "<br/>" .
                "Thank-you again for your help in improving the quality of the achievements on RA!<br>" .
                "<br/>" .
                "-- Your friends at RetroAchievements.org<br/>";


        if( IsAtHome() )
        {
            error_log( __FUNCTION__ . " dumping mail, not sending... no mailserver!" );
            error_log( $email );
            error_log( $emailTitle );
            error_log( $msg );
            $retVal = TRUE;
        }
        else
        {
            error_log( __FUNCTION__ . " sending ticket resolution mail to $user at address $email" );
            $retVal = mail_utf8( $email, "RetroAchievements.org", "Scott@retroachievements.org", $emailTitle, $msg );
            error_log( __FUNCTION__ . " return val: $retVal" );
        }


        return TRUE;
    }
    else
    {
        error_log( __FUNCTION__ . " failed?! $user, $ticketID, $ticketVal" );
        return FALSE;
    }
}

function countOpenTicketsByDev( $dev ) {
    if( $dev == NULL )
        return NULL;

    global $db;

    $dev = mysqli_real_escape_string( $db, $dev );

    $query = "
        SELECT count(*) as count
        FROM Ticket AS tick
        LEFT JOIN Achievements AS ach ON ach.ID = tick.AchievementID
        LEFT JOIN UserAccounts AS ua ON ua.User = ach.Author
        WHERE ach.Author = '$dev' AND tick.ReportState = 1";

    $dbResult = s_mysql_query( $query );

    if( $dbResult !== FALSE )
    {
        return mysqli_fetch_assoc( $dbResult )['count'];
    }
    else
    {
        return FALSE;
    }
}
