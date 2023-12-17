<?php

use App\Community\Enums\ArticleType;

function getMD5List(int $consoleID): array
{
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

    foreach (legacyDbFetchAll($query) as $nextData) {
        $nextData['GameID'] = (int) $nextData['GameID'];
        $retVal[$nextData['MD5']] = $nextData['GameID'];
    }

    return $retVal;
}

function getHashListByGameID(int $gameID): array
{
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

function getGameIDFromMD5(string $md5): int
{
    sanitize_sql_inputs($md5);

    $query = "SELECT GameID FROM GameHashLibrary WHERE MD5='$md5'";
    $dbResult = s_mysql_query($query);

    if ($dbResult !== false && mysqli_num_rows($dbResult) >= 1) {
        $data = mysqli_fetch_assoc($dbResult);

        return (int) $data['GameID'];
    }

    return 0;
}

/**
 * Gets the list of hashes and hash information from the databased using the input offset and count.
 */
function getHashList(int $offset, int $count, ?string $searchedHash): array
{
    sanitize_sql_inputs($searchedHash);

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

    $db = getMysqliConnection();
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
    $db = getMysqliConnection();
    $dbResult = mysqli_query($db, "SELECT COUNT(*) AS TotalHashes FROM GameHashLibrary");

    if (!$dbResult) {
        return 0;
    }

    return (int) mysqli_fetch_assoc($dbResult)['TotalHashes'];
}

function removeHash(string $user, int $gameID, string $hash): bool
{
    sanitize_sql_inputs($hash);

    $query = "DELETE FROM GameHashLibrary WHERE GameID = $gameID AND MD5 = '$hash'";
    $dbResult = s_mysql_query($query);

    $result = $dbResult !== false;

    // Log hash unlink
    addArticleComment("Server", ArticleType::GameHash, $gameID, $hash . " unlinked by " . $user);

    return $result;
}
