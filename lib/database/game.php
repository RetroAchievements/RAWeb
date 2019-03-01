<?php
require_once( __DIR__ . '/../bootstrap.php' );
//////////////////////////////////////////////////////////////////////////////////////////
//	Game Accessors
//////////////////////////////////////////////////////////////////////////////////////////
//	00:21 23/02/2013
function getGameFromHash( $md5Hash, &$gameIDOut, &$gameTitleOut )
{
    $query = "SELECT ID, GameName FROM GameData WHERE GameMD5='$md5Hash'";
    $dbResult = s_mysql_query( $query );

    if( $dbResult !== NULL )
    {
        $db_entry = mysqli_fetch_assoc( $dbResult );
        if( $data !== NULL )
        {
            $gameIDOut = $data[ 'ID' ];
            $gameTitleOut = $data[ 'GameName' ];
            return TRUE;
        }
        else
        {
            error_log( __FUNCTION__ . " cannot find game with md5 ($md5Hash) in DB!" );
            return FALSE;
        }
    }
    else
    {
        error_log( __FUNCTION__ . " issues getting game with md5 ($md5Hash) from DB!" );
        return FALSE;
    }
}

//	11:37 30/10/2014
function GetGameData( $gameID )
{
    $query = "SELECT gd.ID, gd.Title, gd.ConsoleID, gd.ForumTopicID, IFNULL( gd.Flags, 0 ) AS Flags, gd.ImageIcon, gd.ImageTitle, gd.ImageIngame, gd.ImageBoxArt, gd.Publisher, gd.Developer, gd.Genre, gd.Released, gd.IsFinal, c.Name AS ConsoleName, c.ID AS ConsoleID, gd.RichPresencePatch
			  FROM GameData AS gd
			  LEFT JOIN Console AS c ON gd.ConsoleID = c.ID
			  WHERE gd.ID = $gameID";

    $dbResult = s_mysql_query( $query );
    if( $retVal = mysqli_fetch_assoc( $dbResult ) )
    {
        settype( $retVal[ 'ID' ], 'integer' );
        settype( $retVal[ 'ConsoleID' ], 'integer' );
        settype( $retVal[ 'Flags' ], 'integer' );
        settype( $retVal[ 'ForumTopicID' ], 'integer' );
        settype( $retVal[ 'IsFinal' ], 'boolean' );
        return $retVal;
    }
    else
    {
        error_log( $query );
        error_log( __FUNCTION__ . " cannot find game with ID ($gameID) in DB!" );
        return NULL;
    }
}

//	00:48 23/02/2013
function getGameTitleFromID( $gameID, &$gameTitle, &$consoleID, &$consoleName, &$forumTopicID, &$allData )
{
    $gameTitle = "UNRECOGNISED";
    settype( $gameID, "integer" );

    if( $gameID !== 0 )
    {
        $query = "SELECT gd.Title, gd.ForumTopicID, c.ID AS ConsoleID, c.Name AS ConsoleName, gd.Flags, gd.ImageIcon, gd.ImageIcon AS GameIcon, gd.ImageTitle, gd.ImageIngame, gd.ImageBoxArt, gd.Publisher, gd.Developer, gd.Genre, gd.Released
				  FROM GameData AS gd
				  LEFT JOIN Console AS c ON gd.ConsoleID = c.ID
				  WHERE gd.ID=$gameID";
        $dbResult = s_mysql_query( $query );

        if( $dbResult !== FALSE )
        {
            $data = mysqli_fetch_assoc( $dbResult );
            if( $data !== FALSE )
            {
                $gameTitle = $data[ 'Title' ];
                $consoleName = $data[ 'ConsoleName' ];
                $consoleID = $data[ 'ConsoleID' ];
                $forumTopicID = $data[ 'ForumTopicID' ];
                $allData = $data;
            }
            else
            {
                error_log( $query );
                error_log( __FUNCTION__ . " cannot find game with ID ($gameID) in DB!" );
            }
        }
        else
        {
            error_log( $query );
            error_log( __FUNCTION__ . " issues getting game with ID ($gameID) from DB!" );
        }
    }

    return $gameTitle;
}

function getGameMetadata( $gameID, $user, &$achievementDataOut, &$gameDataOut, $sortBy = 0, $user2 = NULL )
{
    return getGameMetadataByFlags( $gameID, $user, $achievementDataOut, $gameDataOut, $sortBy, $user2, NULL );
}

function getGameMetadataByFlags( $gameID, $user, &$achievementDataOut, &$gameDataOut, $sortBy = 0, $user2 = NULL, $flags = 0 )
{
    settype( $gameID, 'integer' );
    settype( $sortBy, 'integer' );
    settype( $flags, 'integer' );

    // flag = 5 -> Unofficial / flag = 3 -> Core
    $flags = $flags != 5 ? 3 : 5;

    switch( $sortBy )
    {
        case 1: // display order defined by the developer
            $orderBy = "ORDER BY ach.DisplayOrder, ach.ID ASC ";
            break;
        case 11:
            $orderBy = "ORDER BY ach.DisplayOrder DESC, ach.ID DESC ";
            break;

        case 2: // won by X users
            $orderBy = "ORDER BY NumAwarded, ach.ID ASC ";
            break;
        case 12:
            $orderBy = "ORDER BY NumAwarded DESC, ach.ID DESC ";
            break;

        // meleu: 3 and 13 should sort by the date the user won the cheevo
        //        but it's not trivial to implement (requires tweaks on SQL query).
        //case 3: // date the user won
            //$orderBy = " ";
            //break;
        //case 13:
            //$orderBy = " ";
            //break;

        case 4: // points
            $orderBy = "ORDER BY ach.Points, ach.ID ASC ";
            break;
        case 14:
            $orderBy = "ORDER BY ach.Points DESC, ach.ID DESC ";
            break;

        case 5: // Title
            $orderBy = "ORDER BY ach.Title, ach.ID ASC ";
            break;
        case 15:
            $orderBy = "ORDER BY ach.Title DESC, ach.ID DESC ";
            break;

        default:
            $orderBy = "ORDER BY ach.DisplayOrder, ach.ID ASC ";
    }

    $gameDataOut = getGameData( $gameID );

    if( $gameDataOut == NULL )
    {
        error_log( __FUNCTION__ . " failed: cannot proceed with above query for user(s) $user:S" );
        return;
    }

    //	Get all achievements data
    //  WHERE reads: If never won, or won by a tracked gamer, or won by me
    //$query = "SELECT ach.ID, ( COUNT( aw.AchievementID ) - SUM( IFNULL( aw.HardcoreMode, 0 ) ) ) AS NumAwarded, SUM( IFNULL( aw.HardcoreMode, 0 ) ) AS NumAwardedHardcore, ach.Title, ach.Description, ach.Points, ach.TrueRatio, ach.Author, ach.DateModified, ach.DateCreated, ach.BadgeName, ach.DisplayOrder, ach.MemAddr
	//		  FROM Achievements AS ach
	//		  LEFT JOIN Awarded AS aw ON aw.AchievementID = ach.ID
    //          LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
	//		  WHERE ( !IFNULL( ua.Untracked, FALSE ) || ua.User = \"$user\" ) AND ach.GameID = $gameID AND ach.Flags = $flags
	//		  GROUP BY ach.ID
	//		  $orderBy";	
	
    $query = "
    SELECT
        ach.ID, 
        IF(
            IFNULL(!ua.Untracked, FALSE) || ua.User = '$user',
            ( COUNT( aw.AchievementID ) - SUM( IFNULL( aw.HardcoreMode, 0 ) ) ),
            0
        ) AS NumAwarded, 
        IF(
            IFNULL(!ua.Untracked, FALSE) || ua.User = '$user',
            ( SUM( IFNULL( aw.HardcoreMode, 0 ) ) ),
            0
        ) AS NumAwardedHardcore, 
        ach.Title,
        ach.Description,
        ach.Points,
        ach.TrueRatio,
        ach.Author,
        ach.DateModified,
        ach.DateCreated,
        ach.BadgeName,
        ach.DisplayOrder,
        ach.MemAddr
    FROM
        Achievements AS ach
    LEFT JOIN
        Awarded AS aw ON aw.AchievementID = ach.ID
    LEFT JOIN
        UserAccounts AS ua ON ua.User = aw.User
    WHERE
        ach.GameID = $gameID AND ach.Flags = $flags
    GROUP BY ach.ID
    $orderBy";

    //echo $query;

    $numAchievements = 0;
    $numDistinctPlayersCasual = 0;
    $numDistinctPlayersHardcore = 0;

    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        while( $data = mysqli_fetch_assoc( $dbResult ) )
        {
            $nextID = $data[ 'ID' ];
            settype( $nextID, 'integer' );
            $achievementDataOut[ $nextID ] = $data;

            $numHC = $data[ 'NumAwardedHardcore' ];
            $numCas = $data[ 'NumAwarded' ];

            if( $numCas > $numDistinctPlayersCasual )
            {
                $numDistinctPlayersCasual = $numCas;
            }
            if( $numHC > $numDistinctPlayersHardcore )
            {
                $numDistinctPlayersHardcore = $numHC;
            }

            $numAchievements++;
        }
    }
    else
    {
        error_log( $query );
        error_log( __FUNCTION__ . " failed: cannot proceed with above query for user(s) $user:S" );
        return;
    }

    //	Now find local information:
    if( isset( $user ) )
    {
        $query = "SELECT ach.ID, aw.Date, aw.HardcoreMode
				  FROM Awarded AS aw
				  LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
				  WHERE ach.GameID = $gameID AND ach.Flags = $flags AND aw.User = '$user'";

        $dbResult = s_mysql_query( $query );
        if( $dbResult !== FALSE )
        {
            while( $data = mysqli_fetch_assoc( $dbResult ) )
            {
                $nextID = $data[ 'ID' ];
                settype( $nextID, 'integer' );
                if( isset( $data[ 'HardcoreMode' ] ) && $data[ 'HardcoreMode' ] == 1 )
                {
                    $achievementDataOut[ $nextID ][ 'DateEarnedHardcore' ] = $data[ 'Date' ];
                }
                else
                {
                    $achievementDataOut[ $nextID ][ 'DateEarned' ] = $data[ 'Date' ];
                }
            }
        }
    }

    if( isset( $user2 ) )
    {
        $query = "SELECT ach.ID, aw.Date, aw.HardcoreMode
				  FROM Awarded AS aw
				  LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
				  WHERE ach.GameID = $gameID AND aw.User = '$user2'";

        $dbResult = s_mysql_query( $query );
        if( $dbResult !== FALSE )
        {
            while( $data = mysqli_fetch_assoc( $dbResult ) )
            {
                $nextID = $data[ 'ID' ];
                settype( $nextID, 'integer' );
                if( $data[ 'HardcoreMode' ] == 1 )
                {
                    $achievementDataOut[ $nextID ][ 'DateEarnedFriendHardcore' ] = $data[ 'Date' ];
                }
                else
                {
                    $achievementDataOut[ $nextID ][ 'DateEarnedFriend' ] = $data[ 'Date' ];
                }
            }
        }
    }

    $gameDataOut[ 'NumAchievements' ] = $numAchievements;
    $gameDataOut[ 'NumDistinctPlayersCasual' ] = $numDistinctPlayersCasual;
    $gameDataOut[ 'NumDistinctPlayersHardcore' ] = $numDistinctPlayersHardcore;

    return $numAchievements;
}

function GetGameAlternatives( $gameID )
{
    settype( $gameID, 'integer' );

    $query = "SELECT gameIDAlt, gd.Title, gd.ImageIcon, c.Name AS ConsoleName, SUM(ach.Points) AS Points, gd.TotalTruePoints
			  FROM GameAlternatives AS ga
			  LEFT JOIN GameData AS gd ON gd.ID = ga.gameIDAlt
			  LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
			  LEFT JOIN Achievements AS ach ON ach.GameID = gd.ID
			  WHERE ga.gameID = $gameID AND IF( ISNULL(ach.Flags), TRUE, ach.Flags = 3 )
			  GROUP BY gd.ID";

    $dbResult = s_mysql_query( $query );

    $results = array();

    if( $dbResult !== FALSE )
    {
        while( $data = mysqli_fetch_assoc( $dbResult ) )
        {
            $results[] = $data;
        }
    }

    return $results;
}

function getGamesListWithNumAchievements( $consoleID, &$dataOut, $sortBy )
{
    return getGamesListByDev( NULL, $consoleID, $dataOut, $sortBy, FALSE );
}

function getGamesListByDev( $dev = NULL, $consoleID, &$dataOut, $sortBy, $ticketsFlag = FALSE )
{
    //	Specify 0 for $consoleID to fetch games for all consoles, or an ID for just that console

    $whereCond = "WHERE ach.Flags=3 ";

    if( $ticketsFlag )
    {
        $selectTickets = ", ticks.OpenTickets";
        $joinTicketsTable = "
        LEFT JOIN (
            SELECT
                ach.GameID,
                count( DISTINCT tick.ID ) AS OpenTickets
            FROM
                Ticket AS tick
            LEFT JOIN
                Achievements AS ach ON ach.ID = tick.AchievementID
            WHERE
                tick.ReportState = 1
            GROUP BY
                ach.GameID
        ) as ticks ON ticks.GameID = ach.GameID ";
    }
    else
    {
        $selectTickets = NULL;
        $joinTicketsTable = NULL;
    }

    if( $consoleID != 0 )
        $whereCond .= "AND gd.ConsoleID=$consoleID ";

    if( $dev != NULL )
        $whereCond .= "AND ach.Author='$dev' ";

    $query = "SELECT gd.Title, ach.GameID AS ID, ConsoleID, COUNT( ach.GameID ) AS NumAchievements, SUM(ach.Points) AS MaxPointsAvailable, lbdi.NumLBs, gd.ImageIcon as GameIcon, gd.TotalTruePoints $selectTickets
				FROM Achievements AS ach
				LEFT JOIN ( SELECT lbd.GameID, COUNT( DISTINCT lbd.ID ) AS NumLBs FROM LeaderboardDef AS lbd GROUP BY lbd.GameID ) AS lbdi ON lbdi.GameID = ach.GameID
                $joinTicketsTable
				INNER JOIN GameData AS gd on gd.ID = ach.GameID
				$whereCond
				GROUP BY ach.GameID ";

    //echo $query;

    settype( $sortBy, 'integer' );

    if( $sortBy < 1 || $sortBy > 13 )
    {
        $sortBy = 1;
    }

    switch( $sortBy )
    {
        case 1:
            $query .= "ORDER BY gd.ConsoleID, Title ";
            break;
        case 11:
            $query .= "ORDER BY gd.ConsoleID, Title DESC ";
            break;

        case 2:
            $query .= "ORDER BY gd.ConsoleID, NumAchievements DESC, Title ";
            break;
        case 12:
            $query .= "ORDER BY gd.ConsoleID, NumAchievements ASC, Title ";
            break;

        case 3:
            $query .= "ORDER BY gd.ConsoleID, MaxPointsAvailable DESC, Title ";
            break;
        case 13:
            $query .= "ORDER BY gd.ConsoleID, MaxPointsAvailable, Title ";
            break;

        case 4:
            $query .= "ORDER BY NumLBs DESC, gd.ConsoleID, MaxPointsAvailable, Title ";
            break;
        case 14:
            $query .= "ORDER BY NumLBs, gd.ConsoleID, MaxPointsAvailable, Title ";
            break;

        case 5:
            if( $ticketsFlag )
                $query .= "ORDER BY OpenTickets DESC, gd.ConsoleID, MaxPointsAvailable, Title ";
            else
                $query .= "ORDER BY gd.ConsoleID, Title ";
            break;
        case 15:
            if( $ticketsFlag )
                $query .= "ORDER BY OpenTickets, gd.ConsoleID, MaxPointsAvailable, Title ";
            else
                $query .= "ORDER BY gd.ConsoleID, Title DESC ";
            break;

        default:
            $query .= "ORDER BY gd.ConsoleID, Title ";
            break;
    }

    $numGamesFound = 0;

    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        while( $db_entry = mysqli_fetch_assoc( $dbResult ) )
        {
            $dataOut[ $numGamesFound ] = $db_entry;
            $numGamesFound++;
        }
    }
    else
    {
        error_log( __FUNCTION__ );
        error_log( $query );
    }

    return $numGamesFound;
}

//	14:01 30/10/2014
function GetGamesListData( $consoleID, $officialFlag = FALSE )
{
    $retVal = array();

    $leftJoinAch = "";
    $whereClause = "";
    if( $officialFlag ) {
        $leftJoinAch = "LEFT JOIN Achievements AS ach ON ach.GameID = gd.ID ";
        $whereClause = "WHERE ach.Flags=3 ";
    }

    //	Specify 0 for $consoleID to fetch games for all consoles, or an ID for just that console
    if( isset( $consoleID ) && $consoleID != 0 )
    {
        $whereClause .= $officialFlag ? "AND " : "WHERE ";
        $whereClause .= "ConsoleID=$consoleID ";
    }

    $query = "SELECT gd.Title, gd.ID, gd.ConsoleID, gd.ImageIcon, c.Name as ConsoleName
			  FROM GameData AS gd
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
			  $leftJoinAch
			  $whereClause
			  ORDER BY ConsoleID, Title";

    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        while( $db_entry = mysqli_fetch_assoc( $dbResult ) )
        {
            $retVal[] = $db_entry;
        }
    }
    return $retVal;
}

//	22:55 20/03/2013
function GetGamesList( $consoleID, &$dataOut )
{
    $dataOut = GetGamesListData( $consoleID );
    return count( $dataOut );
}

function GetGamesListDataNamesOnly( $consoleID, $officialFlag = FALSE )
{
    $retval = array();

    $data = GetGamesListData( $consoleID, $officialFlag );

    foreach( $data as $element )
    {
        $retval[ $element[ 'ID' ] ] = utf8_encode( $element[ 'Title' ] );
    }

    error_log( "GetGamesListDataNamesOnly: " . count( $data ) . ", " . count( $retval ) );

    return $retval;
}

//	14:11 18/04/2013
function getAllocatedForGame( $gameID, &$allocatedPoints, &$numAchievements )
{
    $query = "SELECT SUM(ach.Points) AS AllocatedPoints, COUNT(ID) AS NumAchievements FROM Achievements AS ach ";
    $query .= "WHERE ach.Flags = 3 AND ach.GameID = $gameID";

    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        $data = mysqli_fetch_assoc( $dbResult );
        $allocatedPoints = $data[ 'AllocatedPoints' ];
        $numAchievements = $data[ 'NumAchievements' ];
        return TRUE;
    }
    else
    {
        error_log( __FUNCTION__ );
        error_log( $query );
        return FALSE;
    }
}

//	18:12 24/02/2013
function GetGameIDFromMD5( $md5 )
{
    $query = "SELECT GameID FROM GameHashLibrary WHERE MD5='$md5'";
    $dbResult = s_mysql_query( $query );

    //error_log( $query );
    if( $dbResult !== false && mysqli_num_rows( $dbResult ) >= 1 )
    {
        $data = mysqli_fetch_assoc( $dbResult );
        settype( $data[ 'GameID' ], 'integer' );

        return $data[ 'GameID' ];
    }
    else
    {
        //error_log( __FUNCTION__ . " failed: could not find $md5!" );
        return 0;
    }
}

//	09:02 06/02/2015
function GetAchievementIDs( $gameID )
{
    $retVal = array();
    settype( $gameID, 'integer' );
    $retVal[ 'GameID' ] = $gameID;

    //	Get all achievement IDs
    $query = "SELECT ach.ID AS ID
			  FROM Achievements AS ach
			  WHERE ach.GameID = $gameID AND ach.Flags = 3
			  ORDER BY ach.ID";

    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        $achIDs = array();
        while( $data = mysqli_fetch_assoc( $dbResult ) )
        {
            settype( $data[ 'ID' ], 'integer' );
            $achIDs[] = $data[ 'ID' ];
        }
        $retVal[ 'AchievementIDs' ] = $achIDs;
    }

    return $retVal;
}

//	17:36 23/02/2013
function GetGameIDFromTitle( $gameTitleIn, $consoleID )
{
    $gameTitle = str_replace( "'", "''", $gameTitleIn );
    settype( $consoleID, 'integer' );

    $query = "SELECT ID FROM GameData
			  WHERE Title='$gameTitle' AND ConsoleID='$consoleID'";

    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        $data = mysqli_fetch_assoc( $dbResult );
        return $data[ 'ID' ];
    }
    else
    {
        error_log( $query );
        error_log( __FUNCTION__ . " failed: could not find $gameTitle!" );
        return 0;
    }
}

function testFullyCompletedGame( $user, $achID, $isHardcore )
{
    $achData = [];
    if( getAchievementMetadata( $achID, $achData ) == FALSE )
    {
        error_log( __FUNCTION__ );
        error_log( "cannot get achievement metadata for $achID. This is MEGABAD!" );
        return FALSE;
    }

    $gameID = $achData[ 'GameID' ];

    $query = "SELECT COUNT(ach.ID) AS NumAwarded, COUNT(aw.AchievementID) AS NumAch FROM Achievements AS ach ";
    $query .= "LEFT JOIN Awarded AS aw ON aw.AchievementID = ach.ID AND aw.User = '$user' AND aw.HardcoreMode = $isHardcore ";
    $query .= "WHERE ach.GameID = $gameID AND ach.Flags = 3 ";

    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        $minToCompleteGame = 5;

        $data = mysqli_fetch_assoc( $dbResult );
        if( ( $data[ 'NumAwarded' ] == $data[ 'NumAch' ] ) && ( $data[ 'NumAwarded' ] > $minToCompleteGame ) )
        {
            //	Every achievement earned!
            //error_log( __FUNCTION__ );
            //error_log( "$user earned EVERY achievement for game $gameID" );
            //	Test that this wasn't very recently posted!
            if( RecentlyPostedCompletionActivity( $user, $gameID, $isHardcore ) )
            {
                error_log( "Recently posted about this: ignoring!" );
            }
            else
            {
                postActivity( $user, \RA\ActivityType::CompleteGame, $gameID, $isHardcore );
            }
            return TRUE;
        }
        else
        {
            return FALSE;
        }
    }
    else
    {
        error_log( __FUNCTION__ );
        error_log( "broken1 with $achID, $gameID, $user. This is MEGABAD!" );
        return FALSE;
    }
}

function requestModifyGameData( $gameID, $developerIn, $publisherIn, $genreIn, $releasedIn )
{
    global $db;
    $developer = mysqli_real_escape_string( $db, $developerIn );
    $publisher = mysqli_real_escape_string( $db, $publisherIn );
    $genre = mysqli_real_escape_string( $db, $genreIn );
    $released = mysqli_real_escape_string( $db, $releasedIn );

    $query = "UPDATE GameData AS gd
			  SET gd.Developer = '$developer', gd.Publisher = '$publisher', gd.Genre = '$genre', gd.Released = '$released'
			  WHERE gd.ID = $gameID";

    $dbResult = mysqli_query( $db, $query );

    if( $dbResult == FALSE )
    {
        log_email( __FUNCTION__ . " went wrong. GameID: $gameID, text: $developer, $publisher, $genre, $released " );
        log_email( $query );
    }
    else
    {
        error_log( __FUNCTION__ . " OK! GameID: $gameID, text: $developer, $publisher, $genre, $released" );
    }

    return ( $dbResult != NULL );
}

function requestModifyGameAlt( $gameID, $toAdd = null, $toRemove = null )
{
    if( isset( $toAdd ) && $toAdd > 0 )
    {
        settype( $toAdd, 'integer' );
        $query = "INSERT INTO GameAlternatives VALUES ( $gameID, $toAdd ), ( $toAdd, $gameID )";
        error_log( "Added game alt, $gameID -> $toAdd" );
        s_mysql_query( $query );
    }

    if( isset( $toRemove ) && $toRemove > 0 )
    {
        settype( $toRemove, 'integer' );
        $query = "DELETE FROM GameAlternatives
				  WHERE ( gameID = $gameID AND gameIDAlt = $toRemove ) || ( gameID = $toRemove AND gameIDAlt = $gameID )";
        error_log( "Removed game alt, $gameID -> $toRemove" );
        s_mysql_query( $query );
    }
}

function requestModifyGameForumTopic( $gameID, $newForumTopic )
{
    settype( $gameID, 'integer' );
    settype( $newForumTopic, 'integer' );

    if( $gameID == 0 || $newForumTopic == 0 ) return FALSE;

    if( getTopicDetails( $newForumTopic, $topicData ) )
    {
        global $db;
        $query = "
            UPDATE GameData AS gd
            SET gd.ForumTopicID = '$newForumTopic'
            WHERE gd.ID = $gameID";

        if( mysqli_query( $db, $query ) )
        {
            error_log( __FUNCTION__ . " OK! GameID: $gameID, new ForumTopicID: $newForumTopic" );
            return TRUE;
        }
        else
        {
            log_email( __FUNCTION__ . " went wrong. GameID: $gameID, new ForumTopicID: $newForumTopic" );
            log_email( $query );
            return FALSE;
        }
    }
    return FALSE;
}

//	20:52 06/12/2013
function getAchievementDistribution( $gameID, $hardcore )
{
    settype( $hardcore, 'integer' );
    $retval = array();

    //	Returns an array of the number of players who have achieved each total, up to the max.
    $query = "
		SELECT InnerTable.AwardedCount, COUNT(*) AS NumUniquePlayers
		FROM (
			SELECT COUNT(*) AS AwardedCount
			FROM Awarded AS aw
			LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
			LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
			WHERE gd.ID = $gameID AND aw.HardcoreMode = $hardcore
			GROUP BY aw.User
			ORDER BY AwardedCount DESC
		) AS InnerTable
		GROUP BY InnerTable.AwardedCount";

    $dbResult = s_mysql_query( $query );
    SQL_ASSERT( $dbResult );

    if( $dbResult !== FALSE )
    {
        while( $data = mysqli_fetch_assoc( $dbResult ) )
        {
            $awardedCount = $data[ 'AwardedCount' ];
            $numUnique = $data[ 'NumUniquePlayers' ];
            settype( $awardedCount, 'integer' );
            settype( $numUnique, 'integer' );
            $retval[ $awardedCount ] = $numUnique;
        }
    }

    return $retval;
}

//	01:09 08/12/2013
function getMostPopularGames( $offset, $count, $method )
{
    settype( $method, 'integer' );

    $retval = array();

    if( $method == 0 )
    {
        //	By num awards given:
        $query = "	SELECT gd.ID, gd.Title, gd.ConsoleID, gd.ForumTopicID, gd.Flags, gd.ImageIcon, gd.ImageTitle, gd.ImageIngame, gd.ImageBoxArt, gd.Publisher, gd.Developer, gd.Genre, gd.Released, gd.IsFinal, gd.TotalTruePoints, c.Name AS ConsoleName,     SUM(NumTimesAwarded) AS NumRecords
					FROM GameData AS gd
					LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
					LEFT OUTER JOIN (
                        SELECT
                            COALESCE(aw.cnt, 0) AS NumTimesAwarded,
                            GameID
                        FROM
                            Achievements AS ach
                        LEFT OUTER JOIN (
                            SELECT
                                AchievementID,
                                count(*) cnt
                            FROM
                                Awarded
                            GROUP BY
                                AchievementID) aw ON ach.ID = aw.AchievementID
                        GROUP BY
                            ach.ID) aw ON aw.GameID = gd.ID
					GROUP BY gd.ID
					ORDER BY NumRecords DESC
					LIMIT $offset, $count";
    }
    else
    {
        return $retval;
        // $query = "	SELECT COUNT(*) AS NumRecords, Inner1.*
			// 		FROM
			// 		(
			// 			SELECT gd.ID, gd.Title, gd.ConsoleID, gd.ForumTopicID, gd.Flags, gd.ImageIcon, gd.ImageTitle, gd.ImageIngame, gd.ImageBoxArt, gd.Publisher, gd.Developer, gd.Genre, gd.Released, gd.IsFinal, gd.TotalTruePoints, c.Name AS ConsoleName
			// 			FROM Activity AS act
			// 			LEFT JOIN GameData AS gd ON gd.ID = act.data
			// 			LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
			// 			WHERE act.activitytype = 3 AND !ISNULL( gd.ID )
			// 			GROUP BY gd.ID, act.User
			// 		) AS Inner1
			// 		GROUP BY Inner1.ID
			// 		ORDER BY NumRecords DESC
			// 		LIMIT $offset, $count";
    }

    $dbResult = s_mysql_query( $query );
    SQL_ASSERT( $dbResult );

    if( $dbResult !== FALSE )
    {
        while( $data = mysqli_fetch_assoc( $dbResult ) )
        {
            $retval[] = $data;
        }
    }

    return $retval;
}

//	00:47 30/06/2014
function getGameListSearch( $offset, $count, $method, $consoleID = null )
{
    settype( $method, 'integer' );

    $retval = array();

    if( $method == 0 )
    {
        $where = '';
        if( isset( $consoleID ) && $consoleID > 0 )
        {
            $where = "WHERE gd.ConsoleID = $consoleID ";
        }

        //	By TA:
        $query = "	SELECT gd.ID, gd.Title, gd.ConsoleID, gd.ForumTopicID, gd.Flags, gd.ImageIcon, gd.ImageTitle, gd.ImageIngame, gd.ImageBoxArt, gd.Publisher, gd.Developer, gd.Genre, gd.Released, gd.TotalTruePoints, gd.IsFinal, c.Name AS ConsoleName
					FROM GameData AS gd
					LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
					$where
					ORDER BY gd.TotalTruePoints DESC
					LIMIT $offset, $count";
    }
    else
    {
        //?
    }

    $dbResult = s_mysql_query( $query );
    SQL_ASSERT( $dbResult );

    if( $dbResult !== FALSE )
    {
        while( $data = mysqli_fetch_assoc( $dbResult ) )
        {
            $retval[] = $data;
        }
    }

    return $retval;
}

function getTotalUniquePlayers( $gameID )
{
    settype( $gameID, 'integer' );

    $query = "SELECT COUNT(*) AS UniquePlayers FROM
			  ( SELECT COUNT(aw.User)
			  FROM Awarded AS aw
			  LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
			  LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
			  WHERE gd.ID = $gameID
			  GROUP BY aw.User ) AS InnerTable";

    $dbResult = s_mysql_query( $query );
    SQL_ASSERT( $dbResult );

    $data = mysqli_fetch_assoc( $dbResult );
    return $data[ 'UniquePlayers' ];
}

//	13:40 08/12/2013
function getGameTopAchievers( $gameID, $offset, $count, $requestedBy )
{
    $retval = Array();

    $query = "	SELECT aw.User, SUM(ach.points) AS TotalScore, MAX(aw.Date) AS LastAward
				FROM Awarded AS aw
				LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
				LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
				LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
				WHERE ( !ua.Untracked || ua.User = \"$requestedBy\" ) AND ach.Flags = 3 AND gd.ID = $gameID
				GROUP BY aw.User
				ORDER BY TotalScore DESC, LastAward ASC
				LIMIT $offset, $count";

    $dbResult = s_mysql_query( $query );
    SQL_ASSERT( $dbResult );

    if( $dbResult !== FALSE )
    {
        while( $data = mysqli_fetch_assoc( $dbResult ) )
        {
            $retval[] = $data;
        }
    }

    return $retval;
}

//////////////////////////////////////////////////////////////////////////////////////////
//	Game Title and Alts (Dupe Handling)
//////////////////////////////////////////////////////////////////////////////////////////
function submitAlternativeGameTitle( $user, $md5, $gameTitleDest, $consoleID, &$idOut )
{
    if( !isset( $md5 ) || strlen( $md5 ) != 32 )
    {
        log_email( "invalid md5 provided ($md5) by $user, $gameTitleDest" );
        return FALSE;
    }

    //	Redirect the given md5 to an existing gameID:
    $idOut = getGameIDFromTitle( $gameTitleDest, $consoleID );
    if( $idOut == 0 )
    {
        log_email( "CANNOT find this existing game title! ($user requested $md5 forward to '$gameTitleDest')" );
        return FALSE;
    }

    $query = "SELECT COUNT(*) AS NumEntries, GameID FROM GameHashLibrary WHERE MD5='$md5'";
    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        $data = mysqli_fetch_assoc( $dbResult );
        if( $data[ 'NumEntries' ] == 0 )
        {
            //	Add new name
            $query = "INSERT INTO GameHashLibrary VALUES( '$md5', '$idOut' )";
            log_sql( $query );
            $dbResult = s_mysql_query( $query );
            SQL_ASSERT( $dbResult );

            if( $dbResult !== FALSE )
            {
                //error_log( __FUNCTION__ . " success: $user added ($md5, $idOut) to GameHashLibrary" );
                return TRUE;
            }
            else
            {
                log_sql_fail();
                error_log( $query );
                error_log( __FUNCTION__ . " failed INSERT! $user, $md5 and $idOut" );
                return FALSE;
            }
        }
        else if( $data[ 'NumEntries' ] == 1 )
        {
            //	Looks like it's already here?
            $existingRedirTo = $dbResult[ 'GameID' ];
            if( $existingRedirTo !== $checksumToRedirTo )
            {
                //	Update existing redir entry
                $query = "UPDATE GameHashLibrary SET GameID='$idOut' WHERE MD5='$md5'";
                $dbResult = s_mysql_query( $query );
                if( $dbResult !== FALSE )
                {
                    error_log( __FUNCTION__ . " success: $user updated $md5 from $existingRedirTo to $idOut" );
                    return TRUE;
                }
                else
                {
                    error_log( $query );
                    error_log( __FUNCTION__ . " failed UPDATE! $user, $md5 and $idOut" );
                    return FALSE;
                }
            }
            else
            {
                //	This exact entry is already here.
                error_log( $query );
                error_log( __FUNCTION__ . " failed, already exists! $user, $md5 and $idOut" );
                return FALSE;
            }
        }
        else
        {
            error_log( $query );
            //error_log( __FUNCTION__ . " failed MULTIPLE ENTRIES IN GameHashLibrary! ( " .  $data['NumEntries'] . " ) $user, $md5 and $idOut" );
            log_email( " failed MULTIPLE ENTRIES IN GameHashLibrary! ( " . $data[ 'NumEntries' ] . " ) $user, $md5 and $idOut" );
            return FALSE;
        }
    }
    else
    {
        error_log( $query );
        log_email( __FUNCTION__ . "failed SELECT! $user, $md5 and $idOut" );
        return FALSE;
    }
}

function createNewGame( $title, $consoleID )
{
    settype( $consoleID, 'integer' );
    //$title = str_replace( "--", "-", $title );	//	subtle non-comment breaker

    $query = "INSERT INTO GameData VALUES ( NULL, '$title', $consoleID, NULL, 0, '/Images/000001.png', '/Images/000002.png', '/Images/000002.png', '/Images/000002.png', NULL, NULL, NULL, NULL, 0, NULL, 0 )";
    log_sql( $query );

    global $db;
    $dbResult = mysqli_query( $db, $query );
    if( $dbResult !== FALSE )
    {
        $newID = mysqli_insert_id( $db );
        static_addnewgame( $newID );
        return $newID;
    }

    log_sql_fail();
    error_log( $query );
    error_log( __FUNCTION__ . "failed ($title)" );
    return 0;
}

//	15:38 03/11/2014
function SubmitNewGameTitleJSON( $user, $md5, $titleIn, $consoleID )
{
    settype( $consoleID, 'integer' );

    error_log( __FUNCTION__ . " called with $user, $md5, $titleIn, $consoleID" );

    $retVal = array();
    $retVal[ 'MD5' ] = $md5;
    $retVal[ 'ConsoleID' ] = $consoleID;
    $retVal[ 'GameID' ] = 0;
    $retVal[ 'GameTitle' ] = "";
    $retVal[ 'Success' ] = TRUE;

    $permissions = getUserPermissions( $user );

    if( !isset( $user ) )
    {
        error_log( __FUNCTION__ . " User unset? Ignoring" );
        $retVal[ 'Error' ] = "User doesn't appear to be set or have permissions?";
        $retVal[ 'Success' ] = FALSE;
    }
    else if( strlen( $md5 ) != 32 )
    {
        error_log( __FUNCTION__ . " Md5 unready? Ignoring" );
        $retVal[ 'Error' ] = "MD5 provided ($md5) doesn't appear to be exactly 32 characters, this request is invalid.";
        $retVal[ 'Success' ] = FALSE;
    }
    else if( strlen( $titleIn ) < 2 )
    {
        error_log( __FUNCTION__ . " $user provided a new md5 $md5 for console $consoleID, but provided the title $titleIn. Ignoring" );
        $retVal[ 'Error' ] = "Cannot submit game title given as '$titleIn'";
        $retVal[ 'Success' ] = FALSE;
    }
    else if( $consoleID == 0 )
    {
        error_log( __FUNCTION__ . " cannot submitGameTitle, $consoleID is 0! What console is this for?" );
        $retVal[ 'Error' ] = "Cannot submit game title, ConsoleID is 0! What console is this for?";
        $retVal[ 'Success' ] = FALSE;
    }
    else if( $permissions < \RA\Permissions::Developer )
    {
        error_log( __FUNCTION__ . " Cannot submit *new* game title, not allowed! User level too low ($user, $permissions)" );
        //$retVal[ 'Error' ] = "Cannot submit *new* game title, not allowed! Please apply in forums for 'developer' access.";
        $retVal[ 'Error' ] = "The ROM you are trying to load is not in the database. Check official forum thread for details about versions of the game which are supported.";
	$retVal[ 'Success' ] = FALSE;
    }
    else
    {
        $gameID = GetGameIDFromTitle( $titleIn, $consoleID );
        if( $gameID == 0 )
        {
            //	Remove single quotes, replace with double quotes:
            $title = str_replace( "'", "''", $titleIn );
            $title = str_replace( "/", "-", $title );
            $title = str_replace( "\\", "-", $title );
            error_log( __FUNCTION__ . " about to add $title (was $titleIn)" );

            //	New Game!
            //	The MD5 for this game doesn't yet exist in our DB. Insert a new game:
            $gameID = createNewGame( $title, $consoleID );
            if( $gameID !== 0 )
            {
                $query = "INSERT INTO GameHashLibrary VALUES( '$md5', '$gameID' )";
                log_sql( $query );
                $dbResult = s_mysql_query( $query );
                if( $dbResult !== FALSE )
                {
                    error_log( __FUNCTION__ . " success: $user added $md5, $gameID to GameHashLibrary, and $gameID, $title to GameData" );
                    $retVal[ 'GameID' ] = $gameID;
                    $retVal[ 'GameTitle' ] = $title;
                }
                else
                {
                    log_sql_fail();
                    error_log( $query );
                    error_log( __FUNCTION__ . " failed INSERT! $user, $md5 and $title" );
                    $retVal[ 'Error' ] = "Failed to add $md5 for '$title'";
                    $retVal[ 'Success' ] = FALSE;
                }
            }
            else
            {
                log_email( __FUNCTION__ . "failed: cannot create game $title." );
                $retVal[ 'Error' ] = "Failed to create game title '$title'";
                $retVal[ 'Success' ] = FALSE;
            }
        }
        else
        {
            //	Adding md5 to an existing title ($gameID):
            $query = "INSERT INTO GameHashLibrary VALUES( '$md5', '$gameID' )";
            log_sql( $query );
            $dbResult = s_mysql_query( $query );
            if( $dbResult !== FALSE )
            {
                error_log( __FUNCTION__ . " success: $user added $md5, $gameID to GameHashLibrary, and $gameID, $titleIn to GameData" );
                $retVal[ 'GameID' ] = $gameID;
                $retVal[ 'GameTitle' ] = $titleIn;
            }
            else
            {
                log_email( __FUNCTION__ . "failed: cannot insert duplicate md5 (already present?)" );
                $retVal[ 'Error' ] = "Failed to add duplicate md5 for '$titleIn' (already present?)";
                $retVal[ 'Success' ] = FALSE;
            }
        }
    }

    settype( $retVal[ 'ConsoleID' ], 'integer' );
    settype( $retVal[ 'GameID' ], 'integer' );
    return $retVal;
}

function submitGameTitle( $user, $md5, $titleIn, $consoleID, &$idOut )
{
    if( $consoleID == 0 )
    {
        error_log( __FUNCTION__ . " cannot submitGameTitle, $consoleID is 0! What console is this for?" );
        return FALSE;
    }

    if( strlen( $titleIn ) < 2 )
    {
        error_log( __FUNCTION__ . " $user provided a new md5 $md5 for console $consoleID, but provided the title $titleIn. Ignoring" );
        return FALSE;
    }

    $query = "SELECT GameID FROM GameHashLibrary WHERE MD5='$md5'";
    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        //	Remove single quotes, replace with double quotes:
        $title = str_replace( "'", "''", $titleIn );
        $title = str_replace( "/", "-", $title );
        $title = str_replace( "\\", "-", $title );
        error_log( __FUNCTION__ . " about to add $title (was $titleIn)" );

        if( mysqli_num_rows( $dbResult ) == 0 )
        {
            //	The MD5 for this game doesn't yet exist in our DB. Insert a new game:
            $idOut = createNewGame( $title, $consoleID );

            if( $idOut !== 0 )
            {
                $query = "INSERT INTO GameHashLibrary VALUES( '$md5', '$idOut' )";
                log_sql( $query );
                $dbResult = s_mysql_query( $query );
                if( $dbResult !== FALSE )
                {
                    error_log( __FUNCTION__ . " success: $user added $md5, $idOut to GameHashLibrary, and $idOut, $title to GameData" );
                    return TRUE;
                }
                else
                {
                    log_sql_fail();
                    error_log( $query );
                    error_log( __FUNCTION__ . " failed INSERT! $user, $md5 and $title" );
                    return FALSE;
                }
            }
            else
            {
                log_email( __FUNCTION__ . "failed: cannot create game $title." );
            }
        }
        else
        {
            log_sql_fail();
            error_log( $query );
            error_log( __FUNCTION__ . " unsupported - submitting a game title for a game that already has an associated title." );
            return FALSE;
            //$data = mysqli_fetch_assoc($dbResult);
            //$oldTitle = $data['Title'];
            //$gameID = $data['GameID'];
            ////	Update existing name
            //$query = "UPDATE GameData SET Title='$title' WHERE ID='$gameID'";
            //$dbResult = s_mysql_query( $query );
            //if( $dbResult !== FALSE )
            //{
            //	error_log( __FUNCTION__ . " success: $user updated GameData GameID $gameID from $oldTitle to $title" );
            //	return TRUE;
            //}
            //else
            //{
            //	error_log( $query );
            //	error_log( __FUNCTION__ . " failed UPDATE2! $user, $md5 and $title" );
            //	return FALSE;
            //}
        }
    }
    else
    {
        error_log( $query );
        error_log( __FUNCTION__ . "failed SELECT! $user, $md5 and $title" );
        return FALSE;
    }
}

function requestModifyRichPresence( $gameID, $dataIn )
{
    global $db;

    $dataIn = mysqli_real_escape_string( $db, $dataIn );

    $query = "UPDATE GameData SET RichPresencePatch=\"$dataIn\" WHERE ID=$gameID";

    global $db;
    $dbResult = mysqli_query( $db, $query );
    SQL_ASSERT( $dbResult );

    if( $dbResult )
    {
        error_log( __FUNCTION__ );
        error_log( "$gameID RP is now $dataIn" );

        return TRUE;
    }
    else
    {
        error_log( __FUNCTION__ );
        error_log( "$gameID - $dataIn" );

        return FALSE;
    }
}

function GetRichPresencePatch( $gameID, &$dataOut )
{
    $query = "SELECT gd.RichPresencePatch FROM GameData AS gd WHERE gd.ID = $gameID ";
    $dbResult = s_mysql_query( $query );
    SQL_ASSERT( $dbResult );

    if( $dbResult !== FALSE )
    {
        $data = mysqli_fetch_assoc( $dbResult );
        $dataOut = $data[ 'RichPresencePatch' ];
        return TRUE;
    }
    else
    {
        return FALSE;
    }
}

function RecalculateTrueRatio( $gameID )
{
    $query = "SELECT ach.ID, ach.Points, COUNT(*) AS NumAchieved
			  FROM Achievements AS ach
			  LEFT JOIN Awarded AS aw ON aw.AchievementID = ach.ID
			  WHERE ach.GameID = $gameID AND ach.Flags = 3 AND aw.HardcoreMode = 0
			  GROUP BY ach.ID";

    $dbResult = s_mysql_query( $query );
    SQL_ASSERT( $dbResult );

    if( $dbResult !== FALSE )
    {
        $achData = Array();
        $totalEarners = 0;
        while( $nextData = mysqli_fetch_assoc( $dbResult ) )
        {
            $achData[ $nextData[ 'ID' ] ] = $nextData;
            if( $nextData[ 'NumAchieved' ] > $totalEarners )
                $totalEarners = $nextData[ 'NumAchieved' ];

            //error_log( "Added " . $achData[ $nextData['ID'] ]['ID'] );
        }

        if( $totalEarners == 0 ) // force all unachieved to be 1
            $totalEarners = 1;

        $ratioTotal = 0;

        foreach( $achData as $nextAch )
        {
            $achID = $nextAch[ 'ID' ];
            $achPoints = $nextAch[ 'Points' ];
            $numAchieved = $nextAch[ 'NumAchieved' ];

            if( $numAchieved == 0 ) // force all unachieved to be 1
                $numAchieved = 1;

            $ratioFactor = 0.4;
            $newTrueRatio = ( $achPoints * ( 1.0 - $ratioFactor) ) + ( $achPoints * ( ( $totalEarners / $numAchieved ) * $ratioFactor ) );
            $trueRatio = ( int ) $newTrueRatio;

            $ratioTotal += $trueRatio;

            $query = "UPDATE Achievements AS ach
					  SET ach.TrueRatio = $trueRatio
					  WHERE ach.ID = $achID";
            s_mysql_query( $query );

            //error_log( "TA: $achID -> $trueRatio" );
        }

        $query = "UPDATE GameData AS gd
				  SET gd.TotalTruePoints = $ratioTotal
				  WHERE gd.ID = $gameID";
        s_mysql_query( $query );

        //error_log( __FUNCTION__ . " RECALCULATED " . count($achData) . " achievements for game ID $gameID ($ratioTotal)" );

        return TRUE;
    }
    else
    {
        return FALSE;
    }
}

function GetMD5List( $consoleID )
{
    $retVal = array();

    settype( $consoleID, 'integer' );

    $whereClause = "";
    if( $consoleID > 0 )
        $whereClause = "WHERE gd.ConsoleID = $consoleID ";

    $query = "SELECT MD5, GameID
			  FROM GameHashLibrary AS ghl
			  LEFT JOIN GameData AS gd ON gd.ID = ghl.GameID
			  $whereClause
			  ORDER BY GameID ASC";

    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        while( $nextData = mysqli_fetch_assoc( $dbResult ) )
        {
            settype( $nextData[ 'GameID' ], 'integer' );
            $retVal[ $nextData[ 'MD5' ] ] = $nextData[ 'GameID' ];
            //echo $nextData['MD5'] . ":" . $nextData['GameID'] . "\n";
        }
    }

    return $retVal;
}

function getHashListByGameID( $gameID )
{
    settype( $gameID, 'integer' );
    if( $gameID < 1 )
        return FALSE;

    $query = "
    SELECT MD5 AS hash
    FROM GameHashLibrary
    WHERE GameID = $gameID";

    $dbResult = s_mysql_query( $query );
    if( $dbResult !== FALSE )
    {
        while( $nextData = mysqli_fetch_assoc( $dbResult ) )
            $retVal[] = $nextData;
    }
    else
    {
        err_log( __FUNCTION__ . " failed?!" );
    }

    return $retVal;
}

function isValidConsoleID( $consoleID ) {
    switch( $consoleID )
    {
        case 1: // Mega Drive/Genesis
        case 2: // N64
        case 3: // Super Nintendo
        case 4: // Gameboy
        case 5: // Gameboy Advance
        case 6: // Gameboy Color
        case 7: // NES
        case 8: // PC Engine
        case 11: // Master System
        case 13: // Atari Lynx
        case 14: // Neo Geo Pocket
        case 15: // Game Gear
        case 25: // Atari 2600
        case 27: // Arcade
        case 28: // Virtual Boy
        case 23: // Events (not an actual console)
        case 33: // SG-1000
        case 44: // ColecoVision
        case 47: // PC-8800
        case 51: // Atari7800
            return TRUE;
        default:
            return FALSE;
    }
}
