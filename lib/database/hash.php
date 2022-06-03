<?php

function getMD5List($consoleID): array
{
    sanitize_sql_inputs($consoleID);
    settype($consoleID, 'integer');

    $retVal = [];

    $whereClause = "";
    if ($consoleID > 0) {
        $whereClause = "WHERE gd.ConsoleID = $consoleID ";
    }

    $query = "SELECT MD5, GameID
              FROM GameHashLibrary AS ghl
              LEFT JOIN GameData AS gd ON gd.ID = ghl.GameID
              $whereClause
              ORDER BY GameID ASC";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            settype($nextData['GameID'], 'integer');
            $retVal[$nextData['MD5']] = $nextData['GameID'];
            // echo $nextData['MD5'] . ":" . $nextData['GameID'] . "\n";
        }
    }

    return $retVal;
}

function getHashListByGameID($gameID): array
{
    sanitize_sql_inputs($gameID);
    settype($gameID, 'integer');
    if ($gameID < 1) {
        return [];
    }

    $query = "SELECT MD5 AS Hash, Name, Labels, User
              FROM GameHashLibrary
              WHERE GameID = $gameID
              ORDER BY Name, Hash";

    $retVal = [];
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $nextData;
        }
    }

    return $retVal;
}

function getGameIDFromMD5($md5)
{
    sanitize_sql_inputs($md5);

    $query = "SELECT GameID FROM GameHashLibrary WHERE MD5='$md5'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false && mysqli_num_rows($dbResult) >= 1) {
        $data = mysqli_fetch_assoc($dbResult);
        settype($data['GameID'], 'integer');

        return $data['GameID'];
    } else {
        return 0;
    }
}

/**
 * Gets the list of hashes and hash information from the databased using the input offset and count.
 */
function getHashList($offset, $count, $searchedHash): array
{
    sanitize_sql_inputs($offset, $count, $searchedHash);

    $searchQuery = "";
    if (!empty($searchedHash)) {
        $offset = 0;
        $count = 1;
        $searchQuery = " WHERE h.MD5='" . $searchedHash . "'";
    }

    $query = "
    SELECT
        h.MD5 as Hash,
        h.GameID as GameID,
        h.User as User,
        h.Created as DateAdded,
        gd.Title as GameTitle,
        gd.ImageIcon as GameIcon,
        c.name as ConsoleName
    FROM
        GameHashLibrary h
    LEFT JOIN
        GameData gd ON (h.GameID = gd.ID)
    LEFT JOIN
        Console c ON (gd.ConsoleID = c.ID)
    " . $searchQuery . "
    ORDER BY
        h.Created DESC
    LIMIT $offset, $count";

    global $db;
    $dbResult = mysqli_query($db, $query);

    $retVal = [];

    if ($dbResult !== false) {
        while ($nextData = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $nextData;
        }
    }

    return $retVal;
}

/**
 * Gets the total number of hashes in the database.
 */
function getTotalHashes(): int
{
    global $db;
    $dbResult = mysqli_query($db, "SELECT COUNT(*) AS TotalHashes FROM GameHashLibrary");

    if (!$dbResult) {
        return 0;
    }

    return (int) mysqli_fetch_assoc($dbResult)['TotalHashes'];
}

function updateHashDetails($gameID, $hash, $name, $labels): bool
{
    sanitize_sql_inputs($gameID, $hash, $name, $labels);

    $query = "UPDATE GameHashLibrary SET Name=";

    if (!empty($name)) {
        $query .= "'$name'";
    } else {
        $query .= "NULL";
    }

    $query .= ", Labels = ";

    if (!empty($labels)) {
        $query .= "'$labels'";
    } else {
        $query .= "NULL";
    }

    $query .= " WHERE GameID = $gameID AND MD5 = '$hash'";

    global $db;
    $dbResult = mysqli_query($db, $query);

    if (!$dbResult) {
        log_sql_fail();
    }

    return $dbResult != null;
}
