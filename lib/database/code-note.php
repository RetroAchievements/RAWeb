<?php

use RA\Permissions;

function getCodeNotesData($gameID): array
{
    $codeNotesOut = [];

    sanitize_sql_inputs($gameID);
    settype($gameID, 'integer');

    $query = "SELECT ua.User, cn.Address, cn.Note
              FROM CodeNotes AS cn
              LEFT JOIN UserAccounts AS ua ON ua.ID = cn.AuthorID
              WHERE cn.GameID = '$gameID'
              ORDER BY cn.Address ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            // Seamless :)
            $db_entry['Address'] = sprintf("0x%06x", $db_entry['Address']);
            $codeNotesOut[] = $db_entry;
        }
    } else {
        log_sql_fail();
    }

    return $codeNotesOut;
}

function getCodeNotes($gameID, &$codeNotesOut): bool
{
    sanitize_sql_inputs($gameID);
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
            // Seamless :)
            $db_entry['Address'] = sprintf("0x%06x", $db_entry['Address']);
            $codeNotesOut[$numResults++] = $db_entry;
        }
        return true;
    } else {
        log_sql_fail();
        return false;
    }
}

function submitCodeNote2($user, $gameID, $address, $note): bool
{
    // Prevent <= registered users from creating code notes.
    if (getUserPermissions($user) <= Permissions::Registered) {
        return false;
    }

    $db = getMysqliConnection();

    if (!isset($user) || !isset($gameID) || !isset($address)) {
        return false;
    }

    sanitize_sql_inputs($user, $gameID, $address, $note);

    $addressHex = '0x' . str_pad(dechex($address), 6, '0', STR_PAD_LEFT);
    $currentNotes = getCodeNotesData($gameID);
    $i = array_search($addressHex, array_column($currentNotes, 'Address'));

    if (
        $i !== false
        && getUserPermissions($user) == Permissions::JuniorDeveloper
        && $currentNotes[$i]['User'] !== $user
        && !empty($currentNotes[$i]['Note'])
    ) {
        return false;
    }

    $userID = getUserIDFromUser($user);

    // Nope! $address will be an integer
    // turn '0x00000f' into '15'
    // $addressAsInt = hexdec( substr( $address, 2 ) );

    $note = str_replace("#", "_", $note);   // Remove hashes. Sorry. hash is now a delim.

    $query = "INSERT INTO CodeNotes ( GameID, Address, AuthorID, Note )
              VALUES( '$gameID', '$address', '$userID', '$note' )
              ON DUPLICATE KEY UPDATE AuthorID=VALUES(AuthorID), Note=VALUES(Note)";

    $dbResult = mysqli_query($db, $query);
    return $dbResult !== false;
}

/**
 * Gets the number of code notes created for each game the user has created any notes for.
 */
function getCodeNoteCounts(string $user): array
{
    sanitize_sql_inputs($user);

    $retVal = [];
    $query = "SELECT gd.Title as GameTitle, gd.ImageIcon as GameIcon, c.Name as ConsoleName, cn.GameID as GameID, COUNT(cn.GameID) as TotalNotes,
              SUM(CASE WHEN ua.User = '$user' THEN 1 ELSE 0 END) AS NoteCount
              FROM CodeNotes AS cn
              LEFT JOIN UserAccounts AS ua ON ua.ID = cn.AuthorID
              LEFT JOIN GameData AS gd ON gd.ID = cn.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE LENGTH(Note) > 0
              AND gd.Title IS NOT NULL
              GROUP BY GameID, GameTitle
              HAVING NoteCount > 0
              ORDER BY NoteCount DESC, GameTitle";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }
    return $retVal;
}
