<?php

use App\Community\Enums\ActivityType;
use App\Community\Enums\ArticleType;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementPoints;
use App\Site\Enums\Permissions;
use Illuminate\Support\Collection;

/**
 * @return Collection<int, array>
 */
function getAchievementsList(
    ?string $username,
    int $sortBy,
    int $params,
    int $limit,
    int $offset,
    ?int $achievementFlag = AchievementFlag::OfficialCore,
    ?string $developer = null
): Collection {
    $bindings = [
        'offset' => $offset,
        'limit' => $limit,
    ];

    $innerJoin = "";
    $withAwardedDate = "";
    if ($params > 0 && isValidUsername($username)) {
        $bindings['username'] = $username;
        $innerJoin = "LEFT JOIN Awarded AS aw ON aw.AchievementID = ach.ID AND aw.User = :username";
        $withAwardedDate = ", aw.Date AS AwardedDate";
    }

    // We can't run a sort on a user's achievements AwardedDate
    // if we don't have a user. Bail from the sort.
    if (($sortBy == 9 || $sortBy == 19) && !isValidUsername($username)) {
        $sortBy = 0;
    }

    // TODO slow query (18)
    $query = "SELECT
                    ach.ID, ach.Title AS AchievementTitle, ach.Description, ach.Points, ach.TrueRatio, ach.Author, ach.DateCreated, ach.DateModified, ach.BadgeName, ach.GameID,
                    gd.Title AS GameTitle, gd.ImageIcon AS GameIcon, gd.ConsoleID, c.Name AS ConsoleName
                    $withAwardedDate
                FROM Achievements AS ach
                $innerJoin
                LEFT JOIN GameData AS gd ON gd.ID = ach.GameID
                LEFT JOIN Console AS c ON c.ID = gd.ConsoleID ";

    $bindings['achievementFlag'] = $achievementFlag;
    $query .= "WHERE ach.Flags = :achievementFlag ";
    if ($params == 1) {
        $query .= "AND ( !ISNULL( aw.User ) ) AND aw.HardcoreMode = 0 ";
    }
    if ($params == 2) {
        $query .= "AND ( ISNULL( aw.User ) )  ";
    }
    if (isValidUsername($developer)) {
        $bindings['author'] = $developer;
        $query .= "AND ach.Author = :author ";
    }
    if ($sortBy == 4) {
        $query .= "AND ach.TrueRatio > 0 ";
    }

    if ($params > 0 && isValidUsername($username)) {
        $query .= "GROUP BY ach.ID ";
    }

    switch ($sortBy) {
        case 0:
        case 1:
            $query .= "ORDER BY ach.Title ";
            break;
        case 2:
            $query .= "ORDER BY ach.Description ";
            break;
        case 3:
            $query .= "ORDER BY ach.Points, GameTitle ";
            break;
        case 4:
            $query .= "ORDER BY ach.TrueRatio, ach.Points DESC, GameTitle ";
            break;
        case 5:
            $query .= "ORDER BY ach.Author ";
            break;
        case 6:
            $query .= "ORDER BY GameTitle ";
            break;
        case 7:
            $query .= "ORDER BY ach.DateCreated ";
            break;
        case 8:
            $query .= "ORDER BY ach.DateModified ";
            break;
        case 9:
            $query .= "ORDER BY AwardedDate ";
            break;
        case 11:
            $query .= "ORDER BY ach.Title DESC ";
            break;
        case 12:
            $query .= "ORDER BY ach.Description DESC ";
            break;
        case 13:
            $query .= "ORDER BY ach.Points DESC, GameTitle ";
            break;
        case 14:
            $query .= "ORDER BY ach.TrueRatio DESC, ach.Points, GameTitle ";
            break;
        case 15:
            $query .= "ORDER BY ach.Author DESC ";
            break;
        case 16:
            $query .= "ORDER BY GameTitle DESC ";
            break;
        case 17:
            $query .= "ORDER BY ach.DateCreated DESC ";
            break;
        case 18:
            $query .= "ORDER BY ach.DateModified DESC ";
            break;
        case 19:
            $query .= "ORDER BY AwardedDate DESC ";
            break;
    }

    $query .= "LIMIT :offset, :limit";

    return legacyDbFetchAll($query, $bindings);
}

function GetAchievementData(int $achievementId): ?array
{
    $query = "SELECT ach.ID AS ID, ach.ID AS AchievementID, ach.GameID, ach.Title AS Title, ach.Title AS AchievementTitle, ach.Description, ach.Points, ach.TrueRatio,
                ach.Flags, ach.Author, ach.DateCreated, ach.DateModified, ach.BadgeName, ach.DisplayOrder, ach.AssocVideo, ach.MemAddr,
                c.ID AS ConsoleID, c.Name AS ConsoleName, g.Title AS GameTitle, g.ImageIcon AS GameIcon
              FROM Achievements AS ach
              LEFT JOIN GameData AS g ON g.ID = ach.GameID
              LEFT JOIN Console AS c ON c.ID = g.ConsoleID
              WHERE ach.ID = :achievementId";

    return legacyDbFetch($query, ['achievementId' => $achievementId]);
}

function UploadNewAchievement(
    string $author,
    int $gameID,
    string $title,
    string $desc,
    string $progress,
    string $progressMax,
    string $progressFmt,
    int $points,
    string $mem,
    int $flag,
    ?int &$idInOut,
    string $badge,
    ?string &$errorOut
): bool {
    $gameData = getGameData($gameID);
    $consoleID = $gameData['ConsoleID'];
    $consoleName = $gameData['ConsoleName'];
    $isEventGame = $consoleName == 'Events';
    $userPermissions = getUserPermissions($author);

    // Prevent <= registered users from uploading or modifying achievements
    if ($userPermissions < Permissions::JuniorDeveloper) {
        $errorOut = "You must be a developer to perform this action! Please drop a message in the forums to apply.";

        return false;
    }

    if (!AchievementFlag::isValid($flag)) {
        $errorOut = "Invalid achievement flag";

        return false;
    }

    if ($flag === AchievementFlag::OfficialCore && !isValidConsoleId($consoleID)) {
        $errorOut = "You cannot promote achievements for a game from an unsupported console (console ID: " . $consoleID . ").";

        return false;
    }

    if (!AchievementPoints::isValid($points)) {
        $errorOut = "Invalid points value (" . $points . ").";

        return false;
    }

    $dbAuthor = $author;
    $rawDesc = $desc;
    $rawTitle = $title;
    sanitize_sql_inputs($title, $desc, $mem, $progress, $progressMax, $progressFmt, $dbAuthor);

    if (empty($idInOut)) {
        // New achievement added
        // Prevent users from uploading achievements for games they do not have an active claim on unless it's an event game
        if (!hasSetClaimed($author, $gameID, false) && !$isEventGame) {
            $errorOut = "You must have an active claim on this game to perform this action.";

            return false;
        }

        $query = "
            INSERT INTO Achievements (
                ID, GameID, Title, Description,
                MemAddr, Progress, ProgressMax,
                ProgressFormat, Points, Flags,
                Author, DateCreated, DateModified,
                Updated, VotesPos, VotesNeg,
                BadgeName, DisplayOrder, AssocVideo,
                TrueRatio
            )
            VALUES (
                NULL, '$gameID', '$title', '$desc',
                '$mem', '$progress', '$progressMax',
                '$progressFmt', $points, $flag,
                '$dbAuthor', NOW(), NOW(),
                NOW(), 0, 0,
                '$badge', 0, NULL,
                0
            )";
        $db = getMysqliConnection();
        if (mysqli_query($db, $query) !== false) {
            $idInOut = mysqli_insert_id($db);
            postActivity($author, ActivityType::UploadAchievement, $idInOut);

            static_addnewachievement($idInOut);
            addArticleComment(
                "Server",
                ArticleType::Achievement,
                $idInOut,
                "$author uploaded this achievement.",
                $author
            );

            // uploaded new achievement

            return true;
        }
        // failed
        return false;
    }
    // Achievement being updated
    $query = "SELECT Flags, MemAddr, Points, Title, Description, BadgeName, Author FROM Achievements WHERE ID='$idInOut'";
    $dbResult = s_mysql_query($query);
    if ($dbResult !== false && mysqli_num_rows($dbResult) == 1) {
        $data = mysqli_fetch_assoc($dbResult);

        $changingAchSet = ($data['Flags'] != $flag);
        $changingPoints = ($data['Points'] != $points);
        $changingTitle = ($data['Title'] !== $rawTitle);
        $changingDescription = ($data['Description'] !== $rawDesc);
        $changingBadge = ($data['BadgeName'] !== $badge);
        $changingLogic = ($data['MemAddr'] != $mem);

        if ($flag === AchievementFlag::OfficialCore || $changingAchSet) { // If modifying core or changing achievement state
            // changing ach set detected; user is $author, permissions is $userPermissions, target set is $flag

            // Only allow jr. devs to modify core achievements if they are the author and not updating logic or state
            if ($userPermissions < Permissions::Developer && ($changingLogic || $changingAchSet || $data['Author'] !== $author)) {
                // Must be developer to modify core logic!
                $errorOut = "You must be a developer to perform this action! Please drop a message in the forums to apply.";

                return false;
            }
        }

        if ($flag === AchievementFlag::Unofficial) { // If modifying unofficial
            // Only allow jr. devs to modify unofficial if they are the author
            if ($userPermissions == Permissions::JuniorDeveloper && $data['Author'] !== $author) {
                $errorOut = "You must be a developer to perform this action! Please drop a message in the forums to apply.";

                return false;
            }
        }

        $query = "UPDATE Achievements SET Title='$title', Description='$desc', Progress='$progress', ProgressMax='$progressMax', ProgressFormat='$progressFmt', MemAddr='$mem', Points=$points, Flags=$flag, DateModified=NOW(), Updated=NOW(), BadgeName='$badge' WHERE ID=$idInOut";

        $db = getMysqliConnection();
        if (mysqli_query($db, $query) !== false) {
            // if ($changingAchSet || $changingPoints) {
            //     // When changing achievement set, all existing achievements that rely on this should be purged.
            //     // $query = "DELETE FROM Awarded WHERE ID='$idInOut'";
            //     // nah, that's a bit harsh... esp if you're changing something tiny like the badge!!
            //
            //     // if (s_mysql_query($query) !== false) {
            //     // $rowsAffected = mysqli_affected_rows($db);
            //     // // great
            //     // } else {
            //     // //meh
            //     // }
            // }

            static_setlastupdatedgame($gameID);
            static_setlastupdatedachievement($idInOut);

            postActivity($author, ActivityType::EditAchievement, $idInOut);

            if ($changingAchSet) {
                if ($flag === AchievementFlag::OfficialCore) {
                    addArticleComment(
                        "Server",
                        ArticleType::Achievement,
                        $idInOut,
                        "$author promoted this achievement to the Core set.",
                        $author
                    );
                } elseif ($flag === AchievementFlag::Unofficial) {
                    addArticleComment(
                        "Server",
                        ArticleType::Achievement,
                        $idInOut,
                        "$author demoted this achievement to Unofficial.",
                        $author
                    );
                }
                expireGameTopAchievers($gameID);
            } else {
                $fields = [];
                if ($changingPoints) {
                    $fields[] = "points";
                }
                if ($changingBadge) {
                    $fields[] = "badge";
                }
                if ($changingLogic) {
                    $fields[] = "logic";
                }
                if ($changingTitle) {
                    $fields[] = "title";
                }
                if ($changingDescription) {
                    $fields[] = "description";
                }
                $editString = implode(', ', $fields);

                if (!empty($editString)) {
                    addArticleComment(
                        "Server",
                        ArticleType::Achievement,
                        $idInOut,
                        "$author edited this achievement's $editString.",
                        $author
                    );
                }
            }

            if ($changingPoints || $changingAchSet) {
                $numUnlocks = getAchievementUnlockCount($idInOut);
                if ($numUnlocks > 0) {
                    if ($changingAchSet) {
                        if ($flag === AchievementFlag::OfficialCore) {
                            // promoted to core, restore point attribution
                            attributeDevelopmentAuthor($data['Author'], $numUnlocks, $numUnlocks * $points);
                        } else {
                            // demoted from core, remove point attribution
                            attributeDevelopmentAuthor($data['Author'], -$numUnlocks, -$numUnlocks * $points);
                        }
                    } else {
                        // points changed, adjust point attribution
                        attributeDevelopmentAuthor($data['Author'], 0, $numUnlocks * ($points - (int) $data['Points']));
                    }
                }
            }

            return true;
        }
        log_sql_fail();

        return false;
    }

    return false;
}

function GetAchievementsPatch(int $gameID, int $flag): array
{
    $bindings = [
        'gameId' => $gameID,
    ];

    $flagCond = '';
    if ($flag != 0) {
        $bindings['achievementFlag'] = $flag;
        $flagCond = 'AND Flags=:achievementFlag';
    }

    $query = "SELECT ID, MemAddr, Title, Description, Points, Author, UNIX_TIMESTAMP(DateModified) AS Modified, UNIX_TIMESTAMP(DateCreated) AS Created, BadgeName, Flags
              FROM Achievements
              WHERE GameID=:gameId $flagCond
              ORDER BY DisplayOrder";

    return legacyDbFetchAll($query, $bindings)
        ->map(function ($achievement) {
            $badgeName = $achievement['BadgeName'];
            if ($badgeName) {
                $achievement['BadgeURL'] = media_asset("Badge/$badgeName.png");
                $achievement['BadgeLockedURL'] = media_asset("Badge/{$badgeName}_lock.png");
            }

            return $achievement;
        })
        ->toArray();
}

function GetPatchData(int $gameID, int $flag): array
{
    if (empty($gameID)) {
        return ['Success' => false];
    }

    $gameData = getGameData($gameID);
    if ($gameData === null) {
        return ['Success' => false];
    }

    if ($gameData['ImageIcon']) {
        $gameData['ImageIconURL'] = media_asset($gameData['ImageIcon']);
    }
    if ($gameData['ImageTitle']) {
        $gameData['ImageTitleURL'] = media_asset($gameData['ImageTitle']);
    }
    if ($gameData['ImageIngame']) {
        $gameData['ImageIngameURL'] = media_asset($gameData['ImageIngame']);
    }
    if ($gameData['ImageBoxArt']) {
        $gameData['ImageBoxArtURL'] = media_asset($gameData['ImageBoxArt']);
    }

    // Any IDs sent to the client that aren't under "Achievements" or "Leaderboards"
    // are interpreted as the game ID and mess with Rich Presence pings.
    // See https://discord.com/channels/310192285306454017/310195377993416714/1101532094842273872
    // and https://discord.com/channels/476211979464343552/1002689485005406249/1101552737516257400
    // The system ID and name should have already been copied into "ConsoleID" and "ConsoleName"
    unset($gameData['system']);

    return array_merge($gameData, [
        'Achievements' => GetAchievementsPatch($gameID, $flag),
        'Leaderboards' => GetLBPatch($gameID),
    ]);
}

function updateAchievementDisplayID(int $achID, int $newID): bool
{
    $query = "UPDATE Achievements SET DisplayOrder = $newID, Updated=NOW() WHERE ID = $achID";
    $dbResult = s_mysql_query($query);

    return $dbResult !== false;
}

function updateAchievementEmbedVideo(int $achID, ?string $newURL): bool
{
    $newURL = strip_tags($newURL);
    sanitize_sql_inputs($newURL);

    $query = "UPDATE Achievements SET AssocVideo = '$newURL', Updated=NOW() WHERE ID = $achID";

    $db = getMysqliConnection();
    $dbResult = mysqli_query($db, $query);

    return $dbResult !== false;
}

function updateAchievementFlag(int|string|array $achID, int $newFlag): bool
{
    $achievementIDs = is_array($achID) ? implode(', ', $achID) : $achID;

    sanitize_sql_inputs($achievementIDs, $newFlag);

    $query = "SELECT ID, Author, Points FROM Achievements WHERE ID IN ($achievementIDs) AND Flags != $newFlag";
    $dbResult = s_mysql_query($query);
    if ($dbResult === false) {
        log_sql_fail();

        return false;
    }

    $updatedAchIDs = [];
    $authorCount = [];
    $authorPoints = [];
    while ($data = mysqli_fetch_assoc($dbResult)) {
        $updatedAchID = (int) $data['ID'];
        $updatedAchIDs[] = $updatedAchID;

        $numUnlocks = getAchievementUnlockCount($updatedAchID);
        if ($numUnlocks > 0) {
            if (array_key_exists($data['Author'], $authorCount)) {
                $authorCount[$data['Author']] += $numUnlocks;
                $authorPoints[$data['Author']] += $numUnlocks * (int) $data['Points'];
            } else {
                $authorCount[$data['Author']] = $numUnlocks;
                $authorPoints[$data['Author']] = $numUnlocks * (int) $data['Points'];
            }
        }
    }

    $updatedAchievementIDs = implode(',', $updatedAchIDs);
    if (empty($updatedAchievementIDs)) {
        return true;
    }

    $query = "UPDATE Achievements SET Flags=$newFlag, Updated=NOW() WHERE ID IN ($updatedAchievementIDs)";
    if (!s_mysql_query($query)) {
        log_sql_fail();

        return false;
    }

    foreach ($authorCount as $author => $count) {
        $points = $authorPoints[$author];
        if ($newFlag != AchievementFlag::OfficialCore) {
            $count = -$count;
            $points = -$points;
        }
        attributeDevelopmentAuthor($author, $count, $points);
    }

    return true;
}
