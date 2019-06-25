<?php
require_once(__DIR__ . '/../bootstrap.php');
//////////////////////////////////////////////////////////////////////////////////////////
//    Validation
//////////////////////////////////////////////////////////////////////////////////////////
function validateUser(&$user, $pass, &$fbUser, $permissionRequired)
{
    //    Note: avoid this wherever possible!! Requires raw use of user's password!

    if (!isValidUsername($user)) {
        return false;
    }

    $query = "SELECT User, SaltedPass, fbUser, cookie, Permissions FROM UserAccounts WHERE User='$user'";
    $result = s_mysql_query($query);
    if ($result == false) {
        error_log(__FUNCTION__ . " failed: bad query: $query");
        return false;
    } else {
        $row = mysqli_fetch_array($result);

        //    Add salt
        $saltedHash = md5($pass . getenv('RA_PASSWORD_SALT'));

        if ($row['SaltedPass'] == $saltedHash) {
            $fbUser = $row['fbUser'];
            $user = $row['User'];
            return ($row['Permissions'] >= $permissionRequired);
        } else {
            error_log(__FUNCTION__ . " failed: passwords don't match for user:$user pass:" . $row['SaltedPass']);
            return false;
        }
    }
}

function validateUser_app(&$user, $token, &$fbUser, $permissionRequired)
{
    $fbUser = 0; //    TBD: Remove!
    return RA_ReadTokenCredentials($user, $token, $pointsUnused, $truePointsUnused, $unreadMessagesUnused,
        $permissionsUnused, $permissionRequired);
}

function validateUser_cookie(&$user, $cookie, $permissionRequired, &$permissions = 0)
{
    return validateFromCookie($user, $points, $permissions, $permissionRequired);
}

function validateFromCookie(&$userOut, &$pointsOut, &$permissionsOut, $permissionRequired = 0)
{
    $userOut = RA_ReadCookie("RA_User");
    $cookie = RA_ReadCookie("RA_Cookie");
    if (strlen($userOut) < 2 || strlen($cookie) < 2 || !isValidUsername($userOut)) {
        //    There is no cookie
        return false;
    } else {
        //    Cookie maybe stale: check it!
        $query = "SELECT User, cookie, RAPoints, Permissions FROM UserAccounts WHERE User='$userOut'";
        $dbResult = s_mysql_query($query);
        if ($dbResult == false) {
            error_log(__FUNCTION__ . " failed: bad query: $query");
            return false;
        } else {
            $data = mysqli_fetch_array($dbResult);
            if ($data['cookie'] == $cookie) {
                $pointsOut = $data['RAPoints'];
                $userOut = $data['User']; //    Case correction
                $permissionsOut = $data['Permissions'];
                return ($permissionsOut >= $permissionRequired);
            } else {
                error_log(__FUNCTION__ . " failed: cookie doesn't match for user:$userOut (given: $cookie, should be " . $data['cookie'] . ")");
                return false;
            }
        }
    }
}

function getCookie(&$userOut, &$cookieOut)
{
    $userOut = RA_ReadCookie('RA_User');
    $cookie = RA_ReadCookie('RA_Cookie');
}

function RA_ReadCookieCredentials(
    &$userOut,
    &$pointsOut,
    &$truePointsOut,
    &$unreadMessagesOut,
    &$permissionOut,
    $minPermissions = null
)
{
    //    Promise some values:
    $userOut = RA_ReadCookie('RA_User');
    $cookie = RA_ReadCookie('RA_Cookie');
    $pointsOut = 0;
    $truePointsOut = 0;
    $unreadMessagesOut = 0;
    $permissionOut = 0;

    if (strlen($userOut) < 2 || strlen($cookie) < 10 || !isValidUsername($userOut)) {
        RA_ClearCookie('RA_User');
        RA_ClearCookie('RA_Cookie');
        $userOut = null;
        //error_log( __FUNCTION__ . " User invalid, bailing..." );
        return false;
    }

    $query = "SELECT ua.cookie, ua.RAPoints, ua.UnreadMessageCount, ua.TrueRAPoints, ua.Permissions
              FROM UserAccounts AS ua
              WHERE User='$userOut'";

    $result = s_mysql_query($query);
    if ($result == false) {
        RA_ClearCookie('RA_User');
        RA_ClearCookie('RA_Cookie');
        $userOut = null;

        //error_log( __FUNCTION__ . " failed: bad query: query:$query" );
        return false;
    } else {
        $dbResult = mysqli_fetch_array($result);
        $serverCookie = $dbResult['cookie'];
        if (strcmp($serverCookie, $cookie) !== 0 || $dbResult['Permissions'] == -1) {
            RA_ClearCookie('RA_User');
            RA_ClearCookie('RA_Cookie');
            $userOut = null;

            //error_log( __FUNCTION__ . " failed: bad cookie: query:$query cookies: local:$cookie server:$serverCookie. Removing it!" );
            return false;
        } else {
            userActivityPing($userOut);

            //    Cookies match: now validate permissions if required
            $pointsOut = $dbResult['RAPoints'];
            $unreadMessagesOut = $dbResult['UnreadMessageCount'];
            $truePointsOut = $dbResult['TrueRAPoints'];
            $permissionOut = $dbResult['Permissions'];

            //    Only compare if requested, otherwise return true meaning 'logged in'
            if (isset($minPermissions)) {
                return ($permissionOut >= $minPermissions);
            } else {
                return true;
            }
        }
    }
}

function RA_ReadTokenCredentials(
    &$userOut,
    $token,
    &$pointsOut,
    &$truePointsOut,
    &$unreadMessagesOut,
    &$permissionOut,
    $permissionRequired = null
)
{
    if ($userOut == null || $userOut == '') {
        error_log(__FUNCTION__ . " failed: no user given: $userOut, $token ");
        return false;
    }
    if (!isValidUsername($userOut)) {
        return false;
    }

    $query = "SELECT ua.User, ua.appToken, ua.RAPoints, ua.UnreadMessageCount, ua.TrueRAPoints, ua.Permissions
              FROM UserAccounts AS ua
              WHERE User='$userOut'";
    $result = s_mysql_query($query);
    if ($result == false) {
        error_log(__FUNCTION__ . " failed: bad query: $query");
        return false;
    } else {
        $row = mysqli_fetch_array($result);
        $permissionOut = $row['Permissions'];
        if ($row['appToken'] == $token) {
            $userOut = $row['User']; //    Case correction
            if (isset($permissionRequired)) {
                return ($permissionOut >= $permissionRequired);
            } else {
                return true;
            }
        } else {
            error_log(__FUNCTION__ . " failed: passwords don't match for user:$userOut (given: $token, should be " . $row['appToken'] . ")");
            return false;
        }
    }
}

function RA_ClearCookie($cookieName)
{
    return setcookie("$cookieName", "", 1, '/', AT_HOST_DOT);
}

function RA_ReadCookie($cookieName)
{
    if (RA_CookieExists($cookieName)) {
        return htmlspecialchars($_COOKIE[$cookieName]);
    }

    return null;
}

function RA_SetCookie($cookieName, $cookieValue)
{
    return setcookie("$cookieName", "$cookieValue", time() + 60 * 60 * 24 * 30, '/', AT_HOST_DOT);
}

function RA_CookieExists($cookieName)
{
    return (isset($_COOKIE) &&
        array_key_exists($cookieName, $_COOKIE) &&
        $_COOKIE[$cookieName] !== false);
}

function ValidatePOSTChars($charsIn)
{
    $numChars = strlen($charsIn);
    for ($i = 0; $i < $numChars; $i++) {
        if (!array_key_exists($charsIn[$i], $_POST)) {
            error_log(__FUNCTION__ . " failed, missing " . $charsIn[$i] . " in POST!");
            return false;
        }
    }

    return true;
}

function ValidateGETChars($charsIn)
{
    $numChars = strlen($charsIn);
    for ($i = 0; $i < $numChars; $i++) {
        if (!array_key_exists($charsIn[$i], $_GET)) {
            error_log(__FUNCTION__ . " failed, missing " . $charsIn[$i] . " in GET!");
            return false;
        }
    }

    return true;
}

function ValidatePOSTorGETChars($charsIn)
{
    $numChars = strlen($charsIn);
    for ($i = 0; $i < $numChars; $i++) {
        if (!array_key_exists($charsIn[$i], $_GET)) {
            if (!array_key_exists($charsIn[$i], $_POST)) {
                error_log(__FUNCTION__ . " failed, missing " . $charsIn[$i] . " in GET or POST!");
                return false;
            }
        }
    }

    return true;
}

function generateAPIKey($user)
{
    if (!getAccountDetails($user, $userData)) {
        error_log(__FUNCTION__ . " API Key gen fail 1: not a user?");
        return "";
    }

    if ($userData['Permissions'] < 1) {
        error_log(__FUNCTION__ . " API Key gen fail 2: not a full account!");
        return "";
    }

    $newKey = rand_string(32);

    $query = "UPDATE UserAccounts AS ua
              SET ua.APIKey='$newKey'
              WHERE ua.User = '$user'";

    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        log_email(__FUNCTION__ . " API Key gen fail 3: sql fail?!");
        error_log(__FUNCTION__ . " API Key gen fail 3: sql fail?!");
        return "";
    }

    return $newKey;
}

function GetAPIKey($user)
{
    if (!isValidUsername($user)) {
        return false;
    }

    $query = "SELECT APIKey FROM UserAccounts AS ua
        WHERE ua.User = '$user' AND ua.Permissions >= 1";

    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        error_log(__FUNCTION__);
        error_log("errors fetching API Key for $user!");
        log_email(__FUNCTION__ . " cannot fetch API key for $user");
        return "No API Key found!";
    } else {
        $db_entry = mysqli_fetch_assoc($dbResult);
        return $db_entry['APIKey'];
    }
}

function LogSuccessfulAPIAccess($user)
{
    $query = "UPDATE UserAccounts AS ua
              SET ua.APIUses=ua.APIUses+1
              WHERE ua.User = '$user' ";

    s_mysql_query($query);
}

function ValidateAPIKey($user, $key)
{
    if (strlen($key) < 20 || !isValidUsername($user)) {
        return false;
    }

    $query = "SELECT COUNT(*)
              FROM UserAccounts AS ua
              WHERE ua.User = '$user' AND ua.Permissions >= 1 AND ua.APIKey = '$key' ";

    $dbResult = s_mysql_query($query);

    if ($dbResult == false) {
        error_log(__FUNCTION__);
        error_log("errors validating API Key for $user (given: $key)!");
        log_email(__FUNCTION__ . " errors validating API Key for $user (given: $key)!");
        return false;
    }

    LogSuccessfulAPIAccess($user);

    $data = mysqli_fetch_assoc($dbResult);
    return ($data['COUNT(*)'] != 0);
}

//////////////////////////////////////////////////////////////////////////////////////////
//    Utility
//////////////////////////////////////////////////////////////////////////////////////////
//    23:23 05/03/2014 retired
// function getAvailableBadgesList( &$arrayOut )
// {
// //path to directory to scan
// $directory = "./Badge/";
// //get all image files with a .png extension.
// $filesFound = glob($directory . "*.png");
// //print each file name
// $numFound = 0;
// foreach( $filesFound as $filename )
// {
// //error_log( $filename );
// if( strlen( $filename ) > 8 && $filename[8+5] == '.' )
// {
// //error_log( $filename );
// $newFile = substr( $filename, 8, 5 );
// if( ctype_digit( $newFile ) )
// {
// //error_log( $filename );
// $arrayOut[$numFound] = $newFile;
// $numFound++;
// }
// }
// }
// return $numFound;
// }

function getGameNumUniquePlayersByAwards($gameID)
{
    $query = "SELECT MAX( Inner1.MaxAwarded ) AS TotalPlayers FROM
              (
                  SELECT ach.ID, COUNT(*) AS MaxAwarded
                  FROM Awarded AS aw
                  LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                  LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
                  WHERE gd.ID = $gameID AND aw.HardcoreMode = 0
                  GROUP BY ach.ID
              ) AS Inner1";

    $dbResult = s_mysql_query($query);
    $data = mysqli_fetch_assoc($dbResult);

    return $data['TotalPlayers'];
}

//    16:32 16/10/2014
function getAchievementRecentWinnersData($achID, $offset, $count, $user = null, $friendsOnly = null)
{
    $retVal = array();

    //    Fetch the number of times this has been earned whatsoever (excluding hardcore)
    $query = "SELECT COUNT(*) AS NumEarned, ach.GameID
              FROM Awarded AS aw
              LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
              WHERE AchievementID=$achID AND aw.HardcoreMode = 0";

    $dbResult = s_mysql_query($query);
    $data = mysqli_fetch_assoc($dbResult);

    $retVal['NumEarned'] = $data['NumEarned'];
    settype($retVal['NumEarned'], 'integer');
    $retVal['GameID'] = $data['GameID'];
    settype($retVal['GameID'], 'integer');

    //    Fetch the total number of players for this game:
    $retVal['TotalPlayers'] = getGameNumUniquePlayersByAwards($retVal['GameID']);
    settype($retVal['TotalPlayers'], 'integer');

    $extraWhere = "";
    if (isset($friendsOnly) && $friendsOnly && isset($user) && $user) {
        $extraWhere = " AND aw.User IN ( SELECT Friend FROM Friends WHERE User = '$user' ) ";
    }

    //    Get recent winners, and their most recent activity:
    $query = "SELECT aw.User, ua.RAPoints, UNIX_TIMESTAMP(aw.Date) AS DateAwarded
              FROM Awarded AS aw
              LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
              WHERE AchievementID=$achID AND aw.HardcoreMode = 0 $extraWhere
              ORDER BY aw.Date DESC
              LIMIT $offset, $count";

    $dbResult = s_mysql_query($query);
    while ($db_entry = mysqli_fetch_assoc($dbResult)) {
        //settype( $db_entry['HardcoreMode'], 'integer' );
        settype($db_entry['RAPoints'], 'integer');
        settype($db_entry['DateAwarded'], 'integer');
        $retVal['RecentWinner'][] = $db_entry;
    }

    return $retVal;
}

//    Deprecated (but in use?)
function getAchievementWonData($achID, &$numWinners, &$numPossibleWinners, &$numRecentWinners, &$winnerInfo, $user)
{
    $winnerInfo = array();

    $query = "SELECT COUNT(*) AS NumEarned, ach.GameID
              FROM Awarded AS aw
              LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
              LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
              WHERE ( !ua.Untracked || ua.User = \"$user\" ) AND AchievementID=$achID AND aw.HardcoreMode = 0";
    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        return false;
    }

    $data = mysqli_fetch_assoc($dbResult);
    $numWinners = $data['NumEarned'];
    $gameID = $data['GameID'];   //    Grab GameID at this point
    //$query = "SELECT COUNT(*) FROM UserAccounts WHERE Permissions>0";
    $query = "SELECT MAX( Inner1.MaxAwarded ) AS TotalPlayers FROM
              (
                  SELECT ach.ID, COUNT(*) AS MaxAwarded
                  FROM Awarded AS aw
                  LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                  LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
                  WHERE gd.ID = $gameID AND aw.HardcoreMode = 0
                  GROUP BY ach.ID
              ) AS Inner1";

    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        return false;
    }

    $arrayResult = mysqli_fetch_assoc($dbResult);
    $numPossibleWinners = $arrayResult['TotalPlayers'];

    $numRecentWinners = 0;

    //    Get recent winners, and their most recent activity:
    $query = "SELECT aw.User, ua.RAPoints, aw.Date AS DateAwarded, aw.HardcoreMode
              FROM Awarded AS aw
              LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
              WHERE ( !ua.Untracked || ua.User = \"$user\" ) AND AchievementID=$achID
              ORDER BY aw.Date DESC
              LIMIT 0, 100";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            if (isset($winnerInfo[$db_entry['User']]) && $winnerInfo[$db_entry['User']]['HardcoreMode'] == 1) {
                //    Prefer this value
                continue;
            }

            //    This will overwrite hardcore if found, in order; meaning the result will be
            //    either hardcore has been earned ever, or not at all by this user
            $winnerInfo[$db_entry['User']] = $db_entry;
            $numRecentWinners++;
        }
    }

    if ($user !== null && !array_key_exists($user, $winnerInfo)) {
        //    Do the same again if I wasn't found:
        $query = "SELECT aw.User, aw.Date AS DateAwarded, aw.HardcoreMode
                  FROM Awarded AS aw
                  LEFT JOIN UserAccounts AS ua ON ua.User = aw.User
                  WHERE aw.AchievementID=$achID AND aw.User='$user'
                  ORDER BY aw.Date DESC, HardcoreMode ASC";
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                $winnerInfo[$db_entry['User']] = $db_entry;
                $numRecentWinners++;
            }
        }
    }

    return true;
}

function getConsoleList()
{
    $query = "SELECT ID, Name FROM Console";
    $dbResult = s_mysql_query($query);

    $consoleList = array();

    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $consoleList[$db_entry['ID']] = $db_entry['Name'];
        }
    }

    return $consoleList;
}

function getStaticData()
{
    $query = "SELECT sd.*, ach.Title AS LastAchievementEarnedTitle, gd.Title AS NextGameTitleToScan, gd.ImageIcon AS NextGameToScanIcon, c.Name AS NextGameToScanConsole, ua.User AS NextUserToScan
              FROM StaticData AS sd
              LEFT JOIN Achievements AS ach ON ach.ID = sd.LastAchievementEarnedID
              LEFT JOIN GameData AS gd ON gd.ID = sd.NextGameToScan
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              LEFT JOIN UserAccounts AS ua ON ua.ID = sd.NextUserIDToScan ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        return mysqli_fetch_assoc($dbResult);
    }

    error_log(__FUNCTION__);
    error_log($query);

    return null;
}

function getCodeNotesData($gameID)
{
    $codeNotesOut = array();

    settype($gameID, 'integer');

    $query = "SELECT ua.User, cn.Address, cn.Note
              FROM CodeNotes AS cn
              LEFT JOIN UserAccounts AS ua ON ua.ID = cn.AuthorID
              WHERE cn.GameID = '$gameID'
              ORDER BY cn.Address ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            //    Seamless :)
            $db_entry['Address'] = sprintf("0x%06x", $db_entry['Address']);
            $codeNotesOut[] = $db_entry;
        }
    } else {
        error_log(__FUNCTION__ . " error");
        error_log($query);
    }

    return $codeNotesOut;
}

//    21:26 30/04/2013
function getCodeNotes($gameID, &$codeNotesOut)
{
    settype($gameID, 'integer');

    $query = "SELECT ua.User, cn.Address, cn.Note
              FROM CodeNotes AS cn
              LEFT JOIN UserAccounts AS ua ON ua.ID = cn.AuthorID
              WHERE cn.GameID = $gameID
              ORDER BY cn.Address ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $codeNotesOut = Array();

        $numResults = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            //    Seamless :)
            $db_entry['Address'] = sprintf("0x%06x", $db_entry['Address']);
            $codeNotesOut[$numResults++] = $db_entry;
        }
        return true;
    } else {
        error_log(__FUNCTION__ . " error");
        error_log($query);
        return false;
    }
}

/**
 * @param $user
 * @param $gameID
 * @param $address
 * @param $note
 * @return bool
 */
function submitCodeNote2($user, $gameID, $address, $note)
{
    //    Hack for 'development tutorial game'
    if ($gameID == 10971) {
        return false;
    }

    global $db;

    if (!isset($user) || !isset($gameID) || !isset($address)) {
        return false;
    }

    $addressHex = '0x' . str_pad(dechex($address), 6, '0', STR_PAD_LEFT);
    $currentNotes = getCodeNotesData($gameID);
    $i = array_search($addressHex, array_column($currentNotes, 'Address'));

    if (
        $i !== false
        && getUserPermissions($user) < \RA\Permissions::Developer
        && $currentNotes[$i]['User'] !== $user
        && !empty($currentNotes[$i]['Note'])
    )
    {
        return false;
    }

    $userID = getUserIDFromUser($user);

    //    Nope! $address will be an integer
    //    turn '0x00000f' into '15'
    //$addressAsInt = hexdec( substr( $address, 2 ) );

    $note = mysqli_real_escape_string($db, $note);
    $note = str_replace("#", "_", $note);   //    Remove hashes. Sorry. hash is now a delim.

    $query = "INSERT INTO CodeNotes ( GameID, Address, AuthorID, Note )
              VALUES( '$gameID', '$address', '$userID', '$note' )
              ON DUPLICATE KEY UPDATE AuthorID=VALUES(AuthorID), Note=VALUES(Note)";

    log_sql($query);
    $dbResult = mysqli_query($db, $query);
    return ($dbResult !== false);
}

//    21:55 30/04/2013
/**
 * @param $user
 * @param $gameID
 * @param $address
 * @param $note
 * @return bool
 * @deprecated
 * @see submitCodeNote2()
 */
function submitCodeNote($user, $gameID, $address, $note)
{
    //    Hack for 'development tutorial game'
    if ($gameID == 10971) {
        return false;
    }

    global $db;

    $userID = getUserIDFromUser($user);

    //    turn '0x00000f' into '15'
    $addressAsInt = hexdec(substr($address, 2));

    //$note = str_replace( "'", "''", $note );
    $note = mysqli_real_escape_string($db, $note);

    //    Remove hashes. Sorry. hash is now a delim.
    $note = str_replace("#", "_", $note);

    $query = "UPDATE CodeNotes AS cn
              SET cn.AuthorID = $userID, cn.Note = CONVERT(\"$note\" USING ASCII)
              WHERE cn.Address = $addressAsInt AND cn.GameID = $gameID ";

    log_sql($query);

    $dbResult = mysqli_query($db, $query);
    if ($dbResult !== false) {
        if (mysqli_affected_rows($db) == 0) {
            //    Insert required
            $query = "INSERT INTO CodeNotes VALUES ( $gameID, $addressAsInt, $userID, CONVERT(\"$note\" USING ASCII) )";

            log_sql($query);
            global $db;
            $dbResult = mysqli_query($db, $query);
            if ($dbResult == false) {
                //log_sql_fail();
                error_log(__FUNCTION__ . " error2");
                error_log($query);
                return false;
            } else {
                //    Done :)
                //error_log( __FUNCTION__ . " success2!" );
                //error_log( $query );

                return true;
            }
        } else {
            //    Done :)
            //error_log( __FUNCTION__ . " success1!" );
            //error_log( $query );

            return true;
        }
    } else {
        log_sql_fail();
        error_log(__FUNCTION__ . " error1");
        error_log($query);

        return false;
    }
}

//    23:12 02/05/2013
function performSearch($searchQuery, $offset, $count, &$searchResultsOut)
{
    global $db;

    $resultCount = 0;
    $searchQuery = mysqli_real_escape_string($db, $searchQuery);

    $query = "
    (
        SELECT 'Game' AS Type, gd.ID, CONCAT( '/Game/', gd.ID ) AS Target, gd.Title FROM GameData AS gd
        LEFT JOIN Achievements AS ach ON ach.GameID = gd.ID AND ach.Flags = 3
        WHERE gd.Title LIKE '%$searchQuery%'
        GROUP BY ach.GameID
    )
    UNION
    (
        SELECT 'Achievement' AS Type, ach.ID, CONCAT( '/Achievement/', ach.ID ) AS Target, ach.Title FROM Achievements AS ach
        WHERE ach.Flags = 3 AND ach.Title LIKE '%$searchQuery%'
    )
    UNION
    (
        SELECT 'User' AS Type,
        ua.User AS ID,
        CONCAT( '/User/', ua.User ) AS Target,
        ua.User AS Title
        FROM UserAccounts AS ua
        WHERE ua.User LIKE '%$searchQuery%'
    )
    UNION
    (
        SELECT 'Forum Comment' AS Type,
        ua.User AS ID,
        CONCAT( '/viewtopic.php?t=', ftc.ForumTopicID, '&c=', ftc.ID ) AS Target,
        CONCAT( '...', MID( ftc.Payload, GREATEST( LOCATE('$searchQuery', ftc.Payload)-25, 1), 60 ), '...' ) AS Title
        FROM ForumTopicComment AS ftc
        LEFT JOIN UserAccounts AS ua ON ua.ID = ftc.AuthorID
        WHERE ftc.Payload LIKE '%$searchQuery%'
        GROUP BY ftc.ID DESC
    )
    UNION
    (
        SELECT 'Comment' AS Type, cua.User AS ID,

        IF( c.articletype=1, CONCAT( '/Game/', c.ArticleID ),
            IF( c.articletype=2, CONCAT( '/Achievement/', c.ArticleID ),
                IF( c.articletype=3, CONCAT( '/User/', ua.User ),
                    IF( c.articletype=5, CONCAT( '/feed.php?a=', c.ArticleID ), c.articletype )
                )
            )
        )
        AS Target,

        CONCAT( '...', MID( c.Payload, GREATEST( LOCATE('$searchQuery', c.Payload)-40, 1), 60 ), '...' ) AS Title

        FROM Comment AS c
        LEFT JOIN UserAccounts AS ua ON ( ua.ID = c.ArticleID )
        LEFT JOIN UserAccounts AS cua ON cua.ID = c.UserID
        WHERE c.Payload LIKE '%$searchQuery%'
        GROUP BY c.Submitted DESC
    )
    LIMIT $offset, $count
    ";

    $dbResult = mysqli_query($db, $query);

    if ($dbResult == false) {
        error_log(__FUNCTION__ . " gone wrong!");
        error_log($query);
        log_sql_fail();
    } else {
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $searchResultsOut[$resultCount] = $nextData;
            $resultCount++;
        }
    }

    return $resultCount;
}

function requestModifyGame($author, $gameID, $field, $value)
{
    global $db;

    settype($field, 'integer');
    switch ($field) {
        case 1: // Title
            if (!isset($value) || strlen($value) < 2) {
                log_email("bad data $author, $gameID, $field, $value");
                return false;
            }

            $newTitle = str_replace("'", "''", $value);
            $newTitle = mysqli_real_escape_string($db, $newTitle);
            //$newTitle = str_replace( "/", "&#47;", $newTitle );
            //$newTitle = str_replace( "\\", "&#92;", $newTitle );

            $query = "UPDATE GameData SET Title='$newTitle' WHERE ID=$gameID";
            log_sql("$user: $query");

            $dbResult = mysqli_query($db, $query);

            return ($dbResult !== false);
            break;

        /**
         * UPDATE: do not allow dangerous actions anymore until proper failovers are in place
         */
        // case 2: // GameHashTable
        //     $query = "DELETE FROM GameHashLibrary WHERE GameID=$gameID";
        //     log_sql( "$user: $query" );
        //     $dbResult = s_mysql_query( $query );
        //
        //     return ( $dbResult !== FALSE );
        //     break;

        case 3: // delete a single hash entry
            $query = "DELETE FROM GameHashLibrary WHERE GameID = $gameID AND MD5 = '$value'";
            log_sql("$user: $query");
            $dbResult = s_mysql_query($query);

            return ($dbResult !== false);
            break;
    }

    return false;
}

function recalcScore($user)
{
    $query = "UPDATE UserAccounts SET RAPoints = (
                SELECT SUM(ach.Points) FROM Awarded AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                WHERE aw.User = '$user'
                ),
                TrueRAPoints = (
                SELECT SUM(ach.TrueRatio) FROM Awarded AS aw
                LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                WHERE aw.User = '$user'
                )
              WHERE User = '$user' ";

    $dbResult = s_mysql_query($query);
    if ($dbResult == false) {
        log_email(__FUNCTION__ . " failed for $user!");
        return false;
    } else {
        //error_log( __FUNCTION__ );
        //error_log( "recalc'd $user's score as " . getScore($user) . ", OK!" );
        return true;
    }
}

//    15:12 20/10/2013
function getConsoleIDs()
{
    $retVal = array();

    $query = "SELECT ID, Name FROM Console";
    $dbResult = s_mysql_query($query);

    while ($db_entry = mysqli_fetch_assoc($dbResult)) {
        $retVal[] = $db_entry;
    }

    return $retVal;
}

//    18:05 26/10/2013
function attributeDevelopmentAuthor($author, $points)
{
    $query = "SELECT ContribCount, ContribYield FROM UserAccounts WHERE User = '$author'";
    $dbResult = s_mysql_query($query);
    $oldResults = mysqli_fetch_assoc($dbResult);
    $oldContribCount = $oldResults['ContribCount'];
    $oldContribYield = $oldResults['ContribYield'];

    //    Update the fact that this author made an achievement that just got earned.
    $query = "UPDATE UserAccounts AS ua
              SET ua.ContribCount = ua.ContribCount+1, ua.ContribYield = ua.ContribYield + $points
              WHERE ua.User = '$author'";

    $dbResult = s_mysql_query($query);

    if ($dbResult == false) {
        log_email($query);
        global $db;
        log_email(mysqli_error($db));
    } else {
        //error_log( __FUNCTION__ . " $author, $points" );

        global $developerCountBoundaries;
        global $developerPointBoundaries;

        for ($i = 0; $i < count($developerCountBoundaries); $i++) {
            if ($oldContribCount < $developerCountBoundaries[$i] && $oldContribCount + 1 >= $developerCountBoundaries[$i]) {
                //This developer has arrived at this point boundary!
                AddSiteAward($author, 2, $i);
            }
        }
        for ($i = 0; $i < count($developerPointBoundaries); $i++) {
            if ($oldContribYield < $developerPointBoundaries[$i] && $oldContribYield + $points >= $developerPointBoundaries[$i]) {
                //This developer is newly above this point boundary!
                AddSiteAward($author, 3, $i);
            }
        }
    }
}

function recalculateDevelopmentContributions($user)
{
    //##SD Should be rewritten using a single inner table... damnit!

    $query = "UPDATE UserAccounts AS ua
              SET ua.ContribCount = (
                      SELECT COUNT(*) AS achCount
                      FROM Awarded AS aw
                      LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                      WHERE aw.User != ach.Author AND ach.Author = '$user' AND ach.Flags = 3 ),
              ua.ContribYield = (
                      SELECT SUM(ach.Points) AS achPoints
                      FROM Awarded AS aw
                      LEFT JOIN Achievements AS ach ON ach.ID = aw.AchievementID
                      WHERE aw.User != ach.Author AND ach.Author = '$user' AND ach.Flags = 3 )
              WHERE ua.User = '$user'";

    $dbResult = s_mysql_query($query);

    return ($dbResult != false);
}

//    14:49 14/12/2013
function requestModifyVid($author, &$id, $title, $link)
{
    global $db;

    //    Sanitise:
    $title = mysqli_real_escape_string($db, $title);
    $title = str_replace("'", "''", $title);

    $author = mysqli_real_escape_string($db, $author);
    $link = mysql_real_escape_string($link);

    if (isset($id) && $id != 0) {
        $query = "UPDATE PlaylistVideo SET Title='$title', Author='$author', Link='$link' WHERE ID='$id'";
        log_sql($query);
        global $db;
        $dbResult = mysqli_query($db, $query);

        if ($dbResult !== false) {
            error_log($query);
            error_log(__FUNCTION__ . " updated by $author! $id, $title, $link");
        } else {
            error_log($query);
            error_log(__FUNCTION__ . " failed! $id, $title, $link");
        }
    } else {
        $query = "INSERT INTO PlaylistVideo VALUES ( NULL, '$title', '$author', '$link', NOW() )";
        log_sql($query);
        global $db;
        $dbResult = mysqli_query($db, $query);

        if ($dbResult !== false) {
            error_log($query);
            error_log(__FUNCTION__ . " created by $author! $title, $link");
            $id = mysqli_insert_id($db);
        } else {
            log_sql_fail();
            error_log($query);
            error_log(__FUNCTION__ . " failed2! $title, $link");
        }
    }

    return $id;
}

function SizeTypeToString($char, &$iter)
{
    if ($char == 'H') {
        $iter++;
        return "8-bit ";
    } elseif ($char == 'U') {
        $iter++;
        return "Upper 4-bits ";
    } elseif ($char == 'L') {
        $iter++;
        return "Lower 4-bits ";
    } elseif ($char == 'M') {
        $iter++;
        return "Bit 0 ";
    } elseif ($char == 'N') {
        $iter++;
        return "Bit 1 ";
    } elseif ($char == 'O') {
        $iter++;
        return "Bit 2 ";
    } elseif ($char == 'P') {
        $iter++;
        return "Bit 3 ";
    } elseif ($char == 'Q') {
        $iter++;
        return "Bit 4 ";
    } elseif ($char == 'R') {
        $iter++;
        return "Bit 5 ";
    } elseif ($char == 'S') {
        $iter++;
        return "Bit 6 ";
    } elseif ($char == 'T') {
        $iter++;
        return "Bit 7 ";
    } else {
        //    As-is
        return "16-bit ";
    }
}

function ComparisonOpToString($opChars)
{
    if ($opChars == "<=") {
        return " is less than or equal to ";
    } elseif ($opChars == ">=") {
        return " is greater than or equal to ";
    } elseif ($opChars == "<") {
        return " is less than ";
    } elseif ($opChars == ">") {
        return " is greater than ";
    } elseif ($opChars == "!=") {
        return " is not equal to ";
    } elseif ($opChars == "=") {
        return " is equal to ";
    } else {
        return " unknown sizetype '$opChars' ";
    }
}

function multiexplode($delimiters, $string)
{
    $ready = str_replace($delimiters, $delimiters[0], $string);
    $launch = explode($delimiters[0], $ready);
    return $launch;
}

function getAchievementPatchReadableHTML($mem, $memNotes)
{
    $tableHeader = '
    <tr>
      <th>ID</th>
      <th>Special?</th>
      <th>Type</th>
      <th>Size</th>
      <th>Memory</th>
      <th>Cmp</th>
      <th>Type</th>
      <th>Size</th>
      <th>Mem/Val</th>
      <th>Hits</th>
    </tr>';

    $specialFlags = [
        'R' => 'Reset If',
        'P' => 'Pause If',
        'A' => 'Add Source',
        'B' => 'Sub Source',
        'C' => 'Add Hits',
        'N' => 'And Next',
        '' => ''
    ];

    $memSize = [
        '0xM' => 'Bit0',
        '0xN' => 'Bit1',
        '0xO' => 'Bit2',
        '0xP' => 'Bit3',
        '0xQ' => 'Bit4',
        '0xR' => 'Bit5',
        '0xS' => 'Bit6',
        '0xT' => 'Bit7',
        '0xL' => 'Lower4',
        '0xU' => 'Upper4',
        '0xH' => '8-bit',
        '0xX' => '32-bit', // needs to be before the 16bits below to make the RegEx work
        '0x ' => '16-bit',
        '0x' => '16-bit',
        '' => ''
    ];

    $memTypes = [
        'd' => 'Delta',
        'p' => 'Prior',
        'm' => 'Mem',
        'v' => 'Value',
        '' => ''
    ];

    // kudos to user "stt" for showing that it's possible to parse MemAddr with regex
    $operandRegex = '(d|p)?(' . implode('|', array_keys($memSize)) . ')?([0-9a-f]*)';
    $memRegex = '/(?:([' . implode('',
            array_keys($specialFlags)) . ']):)?' . $operandRegex . '(<=|>=|<|>|=|!=)' . $operandRegex . '(?:[(.](\\d+)[).])?/';
    // memRegex is this monster:
    // (?:([RPABC]):)?(d)?(0xM|0xN|0xO|0xP|0xQ|0xR|0xS|0xT|0xL|0xU|0xH|0xX|0x |0x|)?([0-9a-f]*)(<=|>=|<|>|=|!=)(d)?(0xM|0xN|0xO|0xP|0xQ|0xR|0xS|0xT|0xL|0xU|0xH|0xX|0x |0x|)?([0-9a-f]*)(?:[(.](\d+)[).])?
    // I was about to add comments explaining this long RegEx, but realized that the best way
    // is to copy the regex string and paste it in the proper field at https://regex101.com/

    $res = "\n<table>";

    // separating CoreGroup and AltGroups
    $groups = preg_split("/(?<!0x)S/", $mem);
    for ($i = 0; $i < count($groups); $i++) {
        $res .= "<tr><td colspan=10><p style='text-align: center'><strong>";
        $res .= $i === 0 ? "Core Group" : "Alt Group $i";
        $res .= "</p></strong></td></tr>\n";
        $res .= $tableHeader;

        $codeNotes = [];
        // iterating through the requirements
        $reqs = explode('_', $groups[$i]);
        for ($j = 0; $j < count($reqs); $j++) {
            preg_match_all($memRegex, $reqs[$j], $parsedReq);
            $flag = $parsedReq[1][0];
            $lType = $parsedReq[2][0];
            $lSize = $parsedReq[3][0];
            $lMemory = $parsedReq[4][0];
            $cmp = $parsedReq[5][0];
            $rType = $parsedReq[6][0];
            $rSize = $parsedReq[7][0];
            $rMemVal = $parsedReq[8][0];
            $hits = $parsedReq[9][0];

            $lMemory = '0x' . str_pad(($lSize ? $lMemory : dechex($lMemory)), 6, '0', STR_PAD_LEFT);
            $rMemVal = '0x' . str_pad(($rSize ? $rMemVal : dechex($rMemVal)), 6, '0', STR_PAD_LEFT);
            $hits = $hits ? $hits : "0";
            if ($lType !== "d" && $lType !== "p") {
                $lType = $lSize === '' ? 'v' : 'm';
            }
            if ($rType !== "d" && $rType !== "p") {
                $rType = $rSize === '' ? 'v' : 'm';
            }

            $lTooltip = $rTooltip = null;
            foreach ($memNotes as $nextMemNote) {
                if ($nextMemNote['Address'] === $lMemory) {
                    $lTooltip = " title=\"" . htmlspecialchars($nextMemNote['Note']) . "\"";
                    $codeNotes[$lMemory] = '<strong><u>' . $lMemory . '</u></strong>: ' . htmlspecialchars($nextMemNote['Note']);
                }

                if ($rSize && $nextMemNote['Address'] === $rMemVal) {
                    $rTooltip = " title=\"" . htmlspecialchars($nextMemNote['Note']) . "\"";
                    $codeNotes[$rMemVal] = '<strong><u>' . $rMemVal . '</u></strong>: ' . htmlspecialchars($nextMemNote['Note']);
                }

                if ($lTooltip && $rTooltip) {
                    break;
                }
            }

            $res .= "\n<tr>\n  <td>" . ($j + 1) . "</td>";
            $res .= "\n  <td> " . $specialFlags[$flag] . " </td>";
            $res .= "\n  <td> " . $memTypes[$lType] . " </td>";
            $res .= "\n  <td> " . $memSize[$lSize] . " </td>";
            $res .= "\n  <td" . $lTooltip . "> " . $lMemory . " </td>";
            if ($flag == 'A' || $flag == 'B') {
                $res .= "\n  <td colspan=5 style='text-align: center'> </td>";
            } else {
                $res .= "\n  <td> " . htmlspecialchars($cmp) . " </td>";
                $res .= "\n  <td> " . $memTypes[$rType] . " </td>";
                $res .= "\n  <td> " . $memSize[$rSize] . " </td>";
                $res .= "\n  <td" . $rTooltip . "> " . $rMemVal . " </td>";
                $res .= "\n  <td> (" . $hits . ") </td>";
            }
            $res .= "\n</tr>\n";
        }
        $res .= "<tr><td colspan=10><ul><small>";
        foreach ($codeNotes as $nextCodeNote) {
            $res .= "<li>" . $nextCodeNote . "</li>\n";
        }
        $res .= "</small></ul></td></tr>";
    }
    $res .= "\n</table>\n";

    return $res;
}

