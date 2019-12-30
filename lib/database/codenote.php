<?php
function getCodeNotesData($gameID)
{
    $codeNotesOut = [];

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
        $codeNotesOut = [];

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
    ) {
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
