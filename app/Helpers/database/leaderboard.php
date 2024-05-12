<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\User;
use App\Platform\Enums\ValueFormat;

function SubmitLeaderboardEntry(
    User $user,
    int $lbID,
    int $newEntry,
    ?string $validation
): array {
    $retVal = ['Success' => true];

    $leaderboard = Leaderboard::with('game')->find($lbID);

    if (!$leaderboard) {
        $retVal['Success'] = false;
        $retVal['Error'] = "Cannot find the leaderboard with ID: $lbID";

        return $retVal;
    }

    if ($leaderboard->game->ConsoleID && !isValidConsoleId($leaderboard->game->ConsoleID)) {
        $retVal['Success'] = false;
        $retVal['Error'] = "Cannot submit entry for unsupported console";

        return $retVal;
    }

    $retVal['LBData'] = [
        'Format' => $leaderboard->Format,
        'LeaderboardID' => $leaderboard->id,
        'GameID' => $leaderboard->GameID,
        'Title' => $leaderboard->Title,
        'LowerIsBetter' => $leaderboard->LowerIsBetter,
    ];
    $retVal['Score'] = $newEntry;
    $retVal['ScoreFormatted'] = ValueFormat::format($newEntry, $leaderboard->Format);

    $existingLeaderboardEntry = LeaderboardEntry::where('leaderboard_id', $leaderboard->id)
        ->where('user_id', $user->id)
        ->first();

    if ($existingLeaderboardEntry) {
        // If the user is submitting a better score than their current entry,
        // we'll override the current entry with their new score.
        $comparisonOp = $leaderboard->LowerIsBetter === 1 ? '<' : '>';
        $hasBetterScore =
            ($comparisonOp === '<' && $newEntry < $existingLeaderboardEntry->score)
            || ($comparisonOp === '>' && $newEntry > $existingLeaderboardEntry->score)
        ;

        if ($hasBetterScore) {
            // Update the player's entry.
            $existingLeaderboardEntry->score = $newEntry;
            $existingLeaderboardEntry->save();

            $retVal['BestScore'] = $newEntry;
        } else {
            // No change made.
            $retVal['BestScore'] = $existingLeaderboardEntry->score;
        }
    } else {
        // No existing leaderboard entry. Let's insert a new one.
        LeaderboardEntry::create([
            'leaderboard_id' => $leaderboard->id,
            'user_id' => $user->id,
            'score' => $newEntry,
        ]);

        $retVal['BestScore'] = $newEntry;
    }

    $retVal['TopEntries'] = GetLeaderboardEntriesDataJSON($lbID, $user, 10, 0, false);
    $retVal['TopEntriesFriends'] = GetLeaderboardEntriesDataJSON($lbID, $user, 10, 0, true);
    $retVal['RankInfo'] = GetLeaderboardRankingJSON($user, $lbID, (bool) $leaderboard->LowerIsBetter);

    return $retVal;
}

function removeLeaderboardEntry(User $user, int $lbID, ?string &$score): bool
{
    $leaderboardEntry = LeaderboardEntry::with('leaderboard')
        ->where('leaderboard_id', $lbID)
        ->where('user_id', $user->id)
        ->first();

    if (!$leaderboardEntry) {
        return false;
    }

    $score = ValueFormat::format($leaderboardEntry->score, $leaderboardEntry->leaderboard->Format);

    // TODO utilize soft deletes
    $wasLeaderboardEntryDeleted = $leaderboardEntry->forceDelete();

    return $wasLeaderboardEntryDeleted;
}

function GetLeaderboardRankingJSON(User $user, int $lbID, bool $lowerIsBetter): array
{
    $retVal = [];

    $query = "SELECT COUNT(*) AS UserRank,
                (SELECT COUNT(*) AS NumEntries FROM leaderboard_entries AS le
                 INNER JOIN UserAccounts AS ua ON ua.ID = le.user_id
                 WHERE le.leaderboard_id = $lbID AND NOT ua.Untracked) AS NumEntries
              FROM leaderboard_entries AS lbe
              INNER JOIN leaderboard_entries AS lbe2 ON lbe.leaderboard_id = lbe2.leaderboard_id AND lbe.score " . ($lowerIsBetter ? '<=' : '<') . " lbe2.score
              WHERE lbe.user_id = {$user->id} AND lbe.leaderboard_id = $lbID
              AND NOT (SELECT Untracked FROM UserAccounts WHERE ID = lbe.user_id)
              AND NOT (SELECT Untracked FROM UserAccounts WHERE ID = lbe2.user_id)";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $retVal = mysqli_fetch_assoc($dbResult);

        if ($lowerIsBetter) {
            // Query fetches number of users with scores higher or equal to the player
            // Subtract that number from the total number of players to get the actual rank
            // Top position yields '0', which we should change to '1' for '1st'
            // NOTE: have to use <= for reverse sort so number of users being subtracted
            //       includes all users with the same score (see issue #1201)
            $retVal['Rank'] = (int) $retVal['NumEntries'] - (int) $retVal['UserRank'] + 1;
        } else {
            // Query fetches number of users with scores higher than the player
            // Top position yields '0', which we need to add one.
            $retVal['Rank'] = (int) $retVal['UserRank'] + 1;
        }
    }

    return $retVal;
}

function getLeaderboardsForGame(int $gameID, ?array &$dataOut, ?string $localUser, bool $retrieveHidden = true): int
{
    sanitize_sql_inputs($localUser);

    $retrieveHiddenClause = '';
    if (!$retrieveHidden) {
        $retrieveHiddenClause = "AND lbd.DisplayOrder != -1";
    }

    $query = "SELECT lbd.ID AS LeaderboardID, lbd.Title, lbd.Description, lbd.Format, lbd.DisplayOrder,
                     le2.UserID, ua.User, le2.DateSubmitted, BestEntries.Score
              FROM LeaderboardDef AS lbd
              LEFT JOIN (
                  SELECT lbd.ID as LeaderboardID,
                  CASE WHEN lbd.LowerIsBetter = 0 THEN MAX(le2.Score) ELSE MIN(le2.Score) END AS Score
                  FROM LeaderboardDef AS lbd
                  LEFT JOIN LeaderboardEntry AS le2 ON lbd.ID = le2.LeaderboardID
                  LEFT JOIN UserAccounts AS ua ON ua.ID = le2.UserID
                  WHERE ua.Untracked = 0 AND lbd.GameID = $gameID $retrieveHiddenClause
                  GROUP BY lbd.ID
              ) AS BestEntries ON BestEntries.LeaderboardID = lbd.ID
              LEFT JOIN LeaderboardEntry AS le2 ON le2.LeaderboardID = lbd.ID AND le2.Score = BestEntries.Score
              LEFT JOIN UserAccounts ua ON le2.UserID = ua.ID
              WHERE lbd.GameID = $gameID AND (ua.User IS NULL OR ua.Untracked = 0) $retrieveHiddenClause
              ORDER BY DisplayOrder ASC, LeaderboardID, DateSubmitted ASC";

    $dataOut = [];
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($data = mysqli_fetch_assoc($dbResult)) {
            $lbID = $data['LeaderboardID'];
            if (!isset($dataOut[$lbID])) { // keep earliest entry if players tied
                $dataOut[$lbID] = $data;
            }
        }
    } else {
        log_sql_fail();
    }

    // Get the number of leaderboards for the game
    $query = "SELECT COUNT(*) AS lbCount FROM LeaderboardDef WHERE GameID = $gameID";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        return (int) mysqli_fetch_assoc($dbResult)['lbCount'];
    }

    return 0;
}

function GetLeaderboardEntriesDataJSON(int $lbID, User $user, int $numToFetch, int $offset, bool $friendsOnly): array
{
    $retVal = [];

    // 'Me or my friends'
    $friendQuery = $friendsOnly ? "( ua.User IN ( " . GetFriendsSubquery($user->User) . " ) )" : "TRUE";

    // Get entries:
    $query = "SELECT ua.User, le.score AS Score, UNIX_TIMESTAMP( le.created_at ) AS DateSubmitted
              FROM leaderboard_entries AS le
              LEFT JOIN UserAccounts AS ua ON ua.ID = le.user_id
              LEFT JOIN LeaderboardDef AS lbd ON lbd.ID = le.leaderboard_id
              WHERE le.leaderboard_id = $lbID AND $friendQuery
              ORDER BY
              CASE WHEN lbd.LowerIsBetter = 0 THEN Score END DESC,
              CASE WHEN lbd.LowerIsBetter = 1 THEN Score END ASC, DateSubmitted ASC
              LIMIT $offset, $numToFetch ";

    $dbResult = s_mysql_query($query);
    $numFound = 0;
    while ($nextData = mysqli_fetch_assoc($dbResult)) {
        $nextData['Rank'] = $numFound + $offset + 1;
        $nextData['Score'] = (int) $nextData['Score'];
        $nextData['DateSubmitted'] = (int) $nextData['DateSubmitted'];
        $retVal[] = $nextData;
        $numFound++;
    }

    return $retVal;
}

function GetLeaderboardData(
    int $lbID,
    ?string $user,
    int $numToFetch,
    int $offset,
    bool $nearby = false
): array {
    sanitize_sql_inputs($user);

    $retVal = [];

    // Get raw LB data
    $query = "
      SELECT
        ld.ID AS LBID,
        gd.ID AS GameID,
        gd.Title AS GameTitle,
        ld.LowerIsBetter,
        ld.Title AS LBTitle,
        ld.Description AS LBDesc,
        ld.Format AS LBFormat,
        ld.Mem AS LBMem,
        ua.User AS LBAuthor,
        gd.ConsoleID,
        c.Name AS ConsoleName,
        gd.ForumTopicID,
        gd.ImageIcon AS GameIcon,
        ld.Created AS LBCreated,
        ld.Updated AS LBUpdated,
        (
          SELECT COUNT(user_id)
          FROM leaderboard_entries AS le
          LEFT JOIN UserAccounts AS ua ON ua.ID = le.user_id
          WHERE ua.Untracked = 0 AND le.leaderboard_id = $lbID
        ) AS TotalEntries
      FROM LeaderboardDef AS ld
      LEFT JOIN GameData AS gd ON gd.ID = ld.GameID
      LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
      LEFT JOIN UserAccounts AS ua on ua.ID = ld.author_id
      WHERE ld.ID = $lbID";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $retVal = mysqli_fetch_assoc($dbResult);
        if ($retVal === null) {
            return [];
        }

        $retVal['LBID'] = (int) $retVal['LBID'];
        $retVal['GameID'] = (int) $retVal['GameID'];
        $retVal['LowerIsBetter'] = (int) $retVal['LowerIsBetter'];
        $retVal['ConsoleID'] = (int) $retVal['ConsoleID'];
        $retVal['ForumTopicID'] = (int) $retVal['ForumTopicID'];
        $retVal['TotalEntries'] = (int) $retVal['TotalEntries'];

        $retVal['Entries'] = [];

        // If a $user is passed in and $nearby is true then change $offset to give
        // entries around the player based on their index and total entries
        if ($nearby && !is_null($user)) {
            $userPosition = 0;
            getLeaderboardUserPosition($lbID, $user, $userPosition);
            if ($userPosition != 0) {
                $offset = $userPosition - intdiv($numToFetch, 2) - 1;
                if ($offset <= 0) {
                    $offset = 0;
                } elseif ($retVal['TotalEntries'] - $userPosition + 1 < $numToFetch) {
                    $offset = max(0, $retVal['TotalEntries'] - $numToFetch);
                }
            }
        }

        // Now get entries:
        $query = "SELECT ua.User, le.score AS Score, le.updated_at AS DateSubmitted,
                  CASE WHEN lbd.LowerIsBetter = 0
                  THEN RANK() OVER(ORDER BY Score DESC)
                  ELSE RANK() OVER(ORDER BY Score ASC) END AS UserRank,
                  CASE WHEN lbd.LowerIsBetter = 0
                  THEN ROW_NUMBER() OVER(ORDER BY Score DESC, DateSubmitted ASC)
                  ELSE ROW_NUMBER() OVER(ORDER BY Score ASC, DateSubmitted ASC) END AS UserIndex
                  FROM leaderboard_entries AS le
                  LEFT JOIN UserAccounts AS ua ON ua.ID = le.user_id
                  LEFT JOIN LeaderboardDef AS lbd ON lbd.ID = le.leaderboard_id
                  WHERE (ua.Untracked = 0 || ua.User = '$user' ) AND le.leaderboard_id = $lbID
                  ORDER BY
                  CASE WHEN lbd.LowerIsBetter = 0 THEN Score END DESC,
                  CASE WHEN lbd.LowerIsBetter THEN Score END ASC, DateSubmitted ASC
                  LIMIT $offset, $numToFetch ";

        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            $numResultsFound = 0;
            $userFound = false;

            $entries = [];

            while ($db_entry = mysqli_fetch_assoc($dbResult)) {
                $db_entry['DateSubmitted'] = strtotime($db_entry['DateSubmitted']);
                $db_entry['Score'] = (int) $db_entry['Score'];
                $db_entry['Rank'] = (int) $db_entry['UserRank'];
                unset($db_entry['UserRank']);
                $db_entry['Index'] = (int) $db_entry['UserIndex'];
                unset($db_entry['UserIndex']);

                if (strcmp($db_entry['User'], $user) == 0) {
                    $userFound = true;
                }

                $entries[] = $db_entry;

                $numResultsFound++;
            }

            // Currently only used for appending player to the end on website leaderboard pages
            if ($userFound == false && !$nearby) {
                // Go find user's score in this table, if it exists!
                $query = "SELECT User, Score, DateSubmitted, UserRank, UserIndex FROM
                         (SELECT ua.User, le.score AS Score, le.updated_at AS DateSubmitted,
                          CASE WHEN lbd.LowerIsBetter = 0
                          THEN RANK() OVER(ORDER BY Score DESC)
                          ELSE RANK() OVER(ORDER BY Score ASC) END AS UserRank,
                          CASE WHEN lbd.LowerIsBetter = 0
                          THEN ROW_NUMBER() OVER(ORDER BY Score DESC, DateSubmitted ASC)
                          ELSE ROW_NUMBER() OVER(ORDER BY Score ASC, DateSubmitted ASC) END AS UserIndex
                          FROM leaderboard_entries AS le
                          LEFT JOIN UserAccounts AS ua ON ua.ID = le.user_id
                          LEFT JOIN LeaderboardDef AS lbd ON lbd.ID = le.leaderboard_id
                          WHERE ua.Untracked = 0 AND le.leaderboard_id = $lbID) InnerTable
                          WHERE InnerTable.User = '$user'";

                $dbResult = s_mysql_query($query);
                if ($dbResult !== false) {
                    if (mysqli_num_rows($dbResult) > 0) {
                        // should be 1 or 0?.. I hope...
                        $db_entry = mysqli_fetch_assoc($dbResult);
                        $db_entry['DateSubmitted'] = strtotime($db_entry['DateSubmitted']);
                        $db_entry['Score'] = (int) $db_entry['Score'];
                        $db_entry['Rank'] = (int) $db_entry['UserRank'];
                        // @phpstan-ignore-next-line
                        unset($db_entry['UserRank']);
                        // @phpstan-ignore-next-line
                        $db_entry['Index'] = (int) $db_entry['UserIndex'];
                        // @phpstan-ignore-next-line
                        unset($db_entry['UserIndex']);
                        $entries[] = $db_entry;
                    }
                } else {
                    log_sql_fail();
                }
            }

            $retVal['Entries'] = $entries;
        } else {
            log_sql_fail();
        }
    }

    return $retVal;
}

function getLeaderboardUserPosition(int $lbID, string $user, ?int &$lbPosition): bool
{
    sanitize_sql_inputs($user);

    $query = "SELECT UserIndex FROM
                         (SELECT ua.User, le.score, le.updated_at,
                          CASE WHEN lbd.LowerIsBetter = 0
                          THEN ROW_NUMBER() OVER(ORDER BY le.score DESC, le.updated_at ASC)
                          ELSE ROW_NUMBER() OVER(ORDER BY le.score ASC, le.updated_at ASC) END AS UserIndex
                          FROM leaderboard_entries AS le
                          LEFT JOIN UserAccounts AS ua ON ua.ID = le.user_id
                          LEFT JOIN LeaderboardDef AS lbd ON lbd.ID = le.leaderboard_id
                          WHERE ua.Untracked = 0 AND le.leaderboard_id = $lbID) InnerTable
                          WHERE InnerTable.User = '$user'";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $db_entry = mysqli_fetch_assoc($dbResult);

        if (is_null($db_entry)) {
            $lbPosition = 0;

            return true;
        }

        $lbPosition = $db_entry['UserIndex'];

        return true;
    }
    log_sql_fail();

    return false;
}

function getLeaderboardsList(
    int $gameID,
    int $sortBy,
): array {
    $ifDesc = "";
    if ($sortBy >= 10) {
        $ifDesc = " DESC";
    }

    switch ($sortBy % 10) {
        case 0:
            $orderClause = "ORDER BY ld.DisplayOrder $ifDesc, c.ID, GameTitle";
            break;
        case 2:
            $orderClause = "ORDER BY GameTitle $ifDesc";
            break;
        case 3:
            $orderClause = "ORDER BY ConsoleName $ifDesc, c.ID, GameTitle";
            break;
        case 4:
            $orderClause = "ORDER BY ld.Title $ifDesc";
            break;
        case 5:
            $orderClause = "ORDER BY ld.Description $ifDesc";
            break;
        case 6:
            $orderClause = "ORDER BY ld.LowerIsBetter $ifDesc, ld.Format $ifDesc";
            break;
        case 7:
            $ifDesc = $sortBy == 17 ? "ASC" : "DESC";

            $orderClause = "ORDER BY NumResults $ifDesc";
            break;
        default:
            $orderClause = "ORDER BY ld.ID $ifDesc";
            break;
    }

    $query = "SELECT 
        ld.ID, ld.Title, ld.Description, ld.Format, ld.Mem, ld.DisplayOrder,
        leInner.NumResults,
        ld.LowerIsBetter, ua.User AS Author,
        gd.ID AS GameID, gd.ImageIcon AS GameIcon, gd.Title AS GameTitle,
        c.Name AS ConsoleName, c.ID AS ConsoleID
        FROM LeaderboardDef AS ld
        LEFT JOIN GameData AS gd ON gd.ID = ld.GameID
        LEFT JOIN
        (
            SELECT le.leaderboard_id, COUNT(*) AS NumResults FROM leaderboard_entries AS le
            GROUP BY le.leaderboard_id
            ) AS leInner ON leInner.leaderboard_id = ld.ID
        LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
        LEFT JOIN UserAccounts AS ua ON ua.ID = ld.author_id
        WHERE gd.ID = :gameId
        GROUP BY ld.GameID, ld.ID
        $orderClause";

    return legacyDbFetchAll($query, ['gameId' => $gameID])->toArray();
}

function submitLBData(
    string $user,
    int $lbID,
    string $lbMem,
    string $lbTitle,
    string $lbDescription,
    string $lbFormat,
    bool $lbLowerIsBetter,
    int $lbDisplayOrder
): bool {
    sanitize_sql_inputs($user, $lbMem, $lbTitle, $lbDescription, $lbFormat);

    $lbLowerIsBetter = (int) $lbLowerIsBetter;

    $query = "UPDATE LeaderboardDef AS ld SET
              ld.Mem = '$lbMem',
              ld.Format = '$lbFormat',
              ld.Title = '$lbTitle',
              ld.Description = '$lbDescription',
              ld.Format = '$lbFormat',
              ld.LowerIsBetter = '$lbLowerIsBetter',
              ld.DisplayOrder = '$lbDisplayOrder'
              WHERE ld.ID = $lbID";

    $db = getMysqliConnection();
    $dbResult = mysqli_query($db, $query);
    if ($dbResult !== false) {
        return true;
    }

    return false;
}

function SubmitNewLeaderboard(int $gameID, ?int &$lbIDOut, User $user): bool
{
    if ($gameID == 0) {
        return false;
    }

    $defaultMem = "STA:0x0000=h0010_0xhf601=h0c::CAN:0xhfe13<d0xhfe13::SUB:0xf7cc!=0_d0xf7cc=0::VAL:0xhfe24*1_0xhfe25*60_0xhfe22*3600";
    $query = "INSERT INTO LeaderboardDef (GameID, Mem, Format, Title, Description, LowerIsBetter, DisplayOrder, Author, author_id, Created)
                                VALUES ($gameID, '$defaultMem', 'SCORE', 'My Leaderboard', 'My Leaderboard Description', 0,
                                (SELECT * FROM (SELECT COALESCE(Max(DisplayOrder) + 1, 0) FROM LeaderboardDef WHERE  GameID = $gameID) AS temp), '{$user->User}', {$user->id}, NOW())";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $db = getMysqliConnection();
        $lbIDOut = mysqli_insert_id($db);

        return true;
    }

    return false;
}

function UploadNewLeaderboard(
    string $author,
    int $gameID,
    string $title,
    string $desc,
    string $format,
    bool $lowerIsBetter,
    string $mem,
    ?int &$idInOut,
    ?string &$errorOut
): bool {
    $displayOrder = 0;
    $originalAuthor = '';

    if ($idInOut > 0) {
        $query = "SELECT DisplayOrder, Author FROM LeaderboardDef WHERE ID='$idInOut'";
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false && mysqli_num_rows($dbResult) == 1) {
            $data = mysqli_fetch_assoc($dbResult);
            $displayOrder = $data['DisplayOrder'];
            $originalAuthor = $data['Author'] ?? "Unknown";
            $displayOrder = (int) $displayOrder;
        } else {
            $errorOut = "Unknown leaderboard";

            return false;
        }
    }

    $authorModel = User::firstWhere('User', $author);

    // Prevent non-developers from uploading or modifying leaderboards
    $userPermissions = (int) $authorModel->getAttribute('Permissions');
    if ($userPermissions < Permissions::Developer) {
        if ($userPermissions < Permissions::JuniorDeveloper
            || (!empty($originalAuthor) && $author !== $originalAuthor)) {
            $errorOut = "You must be a developer to perform this action! Please drop a message in the forums to apply.";

            return false;
        }
    }

    if (!isValidConsoleId(getGameData($gameID)['ConsoleID']) && !hasSetClaimed($authorModel, $gameID, false)) {
        $errorOut = "You cannot promote leaderboards for a game from an unsupported console (console ID: " . getGameData($gameID)['ConsoleID'] . ") unless you have an active claim on the game.";

        return false;
    }

    if (!ValueFormat::isValid($format)) {
        $errorOut = "Unknown format: $format";

        return false;
    }

    if (!isset($idInOut) || $idInOut == 0) {
        if (!SubmitNewLeaderboard($gameID, $idInOut, $authorModel)) {
            $errorOut = "Internal error creating new leaderboard.";

            return false;
        }

        $query = "SELECT DisplayOrder FROM LeaderboardDef WHERE ID='$idInOut'";
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false && mysqli_num_rows($dbResult) == 1) {
            $data = mysqli_fetch_assoc($dbResult);
            $displayOrder = $data['DisplayOrder'];
            $displayOrder = (int) $displayOrder;
        }
    }

    if (!submitLBData($author, $idInOut, $mem, $title, $desc, $format, $lowerIsBetter, $displayOrder)) {
        $errorOut = "Internal error updating leaderboard.";

        return false;
    }

    if ($originalAuthor != '') {
        addArticleComment("Server", ArticleType::Leaderboard, $idInOut,
            "$author edited this leaderboard.", $author
        );
    }

    return true;
}

/**
 * Duplicates a leaderboard a specified number of times.
 */
function duplicateLeaderboard(int $gameID, int $leaderboardID, int $duplicateNumber, string $user): bool
{
    if ($gameID == 0) {
        return false;
    }

    // Get the leaderboard info to duplicate
    $getQuery = "
            SELECT Mem,
                   Format,
                   Title,
                   Description,
                   LowerIsBetter,
                   (SELECT Max(DisplayOrder) FROM LeaderboardDef WHERE GameID = $gameID) AS DisplayOrder
            FROM   LeaderboardDef
            WHERE  ID = $leaderboardID";

    $dbResult = s_mysql_query($getQuery);
    if (!$dbResult) {
        return false;
    }

    $db_entry = mysqli_fetch_assoc($dbResult);

    if (empty($db_entry)) {
        return false;
    }

    $lbMem = $db_entry['Mem'];
    $lbFormat = $db_entry['Format'];
    $lbTitle = $db_entry['Title'];
    $lbDescription = $db_entry['Description'];
    $lbScoreType = $db_entry['LowerIsBetter'];
    $lbDisplayOrder = $db_entry['DisplayOrder'];

    // Create the duplicate entries
    for ($i = 1; $i <= $duplicateNumber; $i++) {
        $query = "INSERT INTO LeaderboardDef (GameID, Mem, Format, Title, Description, LowerIsBetter, DisplayOrder, Author, Created)
                                    VALUES ($gameID, '$lbMem', '$lbFormat', '$lbTitle', '$lbDescription', $lbScoreType, ($lbDisplayOrder + $i), '$user', NOW())";
        $dbResult = s_mysql_query($query);
        if ($dbResult !== false) {
            $db = getMysqliConnection();
            mysqli_insert_id($db);
        } else {
            return false;
        }
    }

    return true;
}

function requestResetLB(int $lbID): bool
{
    $entries = LeaderboardEntry::where('leaderboard_id', $lbID);
    $entriesDeleted = $entries->delete();

    // When `delete()` returns false, it indicates an error has occurred.
    return $entriesDeleted !== false;
}

function requestDeleteLB(int $lbID): bool
{
    $leaderboard = Leaderboard::find($lbID);

    if (!$leaderboard) {
        return false;
    }

    $leaderboard->forceDelete();

    return true;
}
