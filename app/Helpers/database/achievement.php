<?php

use App\Community\Enums\ActivityType;
use App\Community\Enums\ArticleType;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementPoints;
use App\Platform\Enums\AchievementType;
use App\Platform\Events\AchievementCreated;
use App\Platform\Events\AchievementPointsChanged;
use App\Platform\Events\AchievementPublished;
use App\Platform\Events\AchievementTypeChanged;
use App\Platform\Events\AchievementUnpublished;
use App\Platform\Models\Achievement;
use App\Site\Enums\Permissions;
use App\Site\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * @return Collection<int, array>
 */
function getAchievementsList(
    ?User $user,
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
    if ($params > 0 && $user) {
        $innerJoin = "LEFT JOIN player_achievements AS pa ON pa.achievement_id = ach.ID AND pa.user_id = " . $user->id;
        $withAwardedDate = ", COALESCE(pa.unlocked_hardcore_at, pa.unlocked_at) AS AwardedDate";
    }

    // We can't run a sort on a user's achievements AwardedDate
    // if we don't have a user. Bail from the sort.
    if (($sortBy == 9 || $sortBy == 19) && !$user) {
        $sortBy = 0;
    }

    // TODO slow query (18)
    $query = "SELECT
                    ach.ID, ach.Title AS AchievementTitle, ach.Description, ach.Points, ach.TrueRatio, ach.Author, ach.DateCreated, ach.DateModified, ach.BadgeName, ach.GameID,
                    gd.Title AS GameTitle, gd.ImageIcon AS GameIcon, gd.ConsoleID, c.Name AS ConsoleName
                    $withAwardedDate
                FROM Achievements AS ach
                $innerJoin
                INNER JOIN GameData AS gd ON gd.ID = ach.GameID
                INNER JOIN Console AS c ON c.ID = gd.ConsoleID ";

    $bindings['achievementFlag'] = $achievementFlag;
    $query .= "WHERE ach.Flags = :achievementFlag ";

    // 1 = my unlocked achievements
    // 2 = achievements i haven't unlocked
    // 3 = official
    // 5 = unofficial
    if ($params == 1) {
        $query .= "AND pa.unlocked_at IS NOT NULL ";
    } elseif ($params == 2) {
        $query .= "AND pa.unlocked_at IS NULL ";
    }

    if (isValidUsername($developer)) {
        $bindings['author'] = $developer;
        $query .= "AND ach.Author = :author ";
    }

    if ($sortBy == 4) {
        $query .= "AND ach.TrueRatio > 0 ";
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
                ach.Flags, ach.type, ach.Author, ach.DateCreated, ach.DateModified, ach.BadgeName, ach.DisplayOrder, ach.AssocVideo, ach.MemAddr,
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
    ?string $type,
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

    if ($type !== null && (!AchievementType::isValid($type) && $type !== 'not-given')) {
        $errorOut = "Invalid achievement type";

        return false;
    }

    $typeValue = "";
    if ($type === null || trim($type) === '' || $type === 'not-given') {
        $typeValue = "NULL";
    } else {
        $typeValue = "'$type'";
    }

    if (empty($idInOut)) {
        // New achievement added
        // Prevent users from uploading achievements for games they do not have an active claim on unless it's an event game
        if (!hasSetClaimed($author, $gameID, false) && !$isEventGame) {
            $errorOut = "You must have an active claim on this game to perform this action.";

            return false;
        }

        $achievement = new Achievement();
        $achievement->GameID = $gameID;
        $achievement->Title = $title;
        $achievement->Description = $desc;
        $achievement->MemAddr = $mem;
        $achievement->Points = $points;
        $achievement->Flags = $flag;
        $achievement->type = ($typeValue == 'NULL') ? null : $type;
        $achievement->Author = $author;
        $achievement->BadgeName = $badge;

        $achievement->save();
        $idInOut = $achievement->ID;
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
        AchievementCreated::dispatch($achievement);

        return true;
    }

    // Achievement being updated
    $achievement = Achievement::find($idInOut);
    if ($achievement) {
        $fields = [];

        $changingPoints = ($achievement->Points != $points);
        if ($changingPoints) {
            $achievement->Points = $points;
            $fields[] = "points";
        }

        if ($achievement->BadgeName !== $badge) {
            $achievement->BadgeName = $badge;
            $fields[] = "badge";
        }

        if ($achievement->Title !== $title) {
            $achievement->Title = $title;
            $fields[] = "title";
        }

        if ($achievement->Description !== $desc) {
            $achievement->Description = $desc;
            $fields[] = "description";
        }

        $changingType = ($achievement->type != $type && $type !== 'not-given');
        if ($changingType) {
            $achievement->type = $type;
            $fields[] = "type";
        }

        $changingLogic = ($achievement->MemAddr != $mem);
        if ($changingLogic) {
            $achievement->MemAddr = $mem;
            $fields[] = "logic";
        }

        $changingAchSet = ($achievement->Flags != $flag);
        if ($changingAchSet) {
            $achievement->Flags = $flag;
        }

        if ($flag === AchievementFlag::OfficialCore || $changingAchSet) { // If modifying core or changing achievement state
            // changing ach set detected; user is $author, permissions is $userPermissions, target set is $flag

            // Only allow jr. devs to modify core achievements if they are the author and not updating logic or state
            if ($userPermissions < Permissions::Developer && ($changingLogic || $changingAchSet || $achievement->Author !== $author)) {
                // Must be developer to modify core logic!
                $errorOut = "You must be a developer to perform this action! Please drop a message in the forums to apply.";

                return false;
            }
        }

        if ($flag === AchievementFlag::Unofficial) { // If modifying unofficial
            // Only allow jr. devs to modify unofficial if they are the author
            if ($userPermissions == Permissions::JuniorDeveloper && $achievement->Author !== $author) {
                $errorOut = "You must be a developer to perform this action! Please drop a message in the forums to apply.";

                return false;
            }
        }

        if ($achievement->isDirty()) {
            $achievement->save();

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
                    AchievementPublished::dispatch($achievement);
                } elseif ($flag === AchievementFlag::Unofficial) {
                    addArticleComment(
                        "Server",
                        ArticleType::Achievement,
                        $idInOut,
                        "$author demoted this achievement to Unofficial.",
                        $author
                    );
                    AchievementUnpublished::dispatch($achievement);
                }
                expireGameTopAchievers($gameID);
            } else {
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

            if ($changingPoints) {
                AchievementPointsChanged::dispatch($achievement);
            }
            if ($changingType) {
                AchievementTypeChanged::dispatch($achievement);
            }
        }

        return true;
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

function updateAchievementFlag(int|string|array $achID, int $newFlag): void
{
    $achievementIDs = is_array($achID) ? $achID : [$achID];

    $achievements = Achievement::whereIn('ID', $achievementIDs)
        ->whereNot('Flags', $newFlag);

    if (!$achievements->count()) {
        return;
    }

    $achievements->update(['Flags' => $newFlag]);

    $updatedAchievements = $achievements->get();

    foreach ($updatedAchievements as $achievement) {
        if ($newFlag === AchievementFlag::OfficialCore) {
            AchievementPublished::dispatch($achievement);
        }

        if ($newFlag === AchievementFlag::Unofficial) {
            AchievementUnpublished::dispatch($achievement);
        }
    }
}

function updateAchievementType(int|string|array $achID, ?string $newType): bool
{
    $achievementIds = is_array($achID) ? $achID : [$achID];

    Achievement::whereIn('ID', $achievementIds)->update(['type' => $newType, 'Updated' => Carbon::now()]);

    return true;
}

function buildBeatenGameCreditDialogContext(array $achievements): string
{
    $softcoreUnlocks = [];
    $hardcoreUnlocks = [];
    foreach ($achievements as $achievementId => $achievement) {
        if (isset($achievement['DateEarned'])) {
            $softcoreUnlocks[] = $achievementId;
        }
        if (isset($achievement['DateEarnedHardcore'])) {
            $hardcoreUnlocks[] = $achievementId;
        }
    }

    $dialogContext = "s:" . implode(",", $softcoreUnlocks) . "|h:" . implode(",", $hardcoreUnlocks);

    return $dialogContext;
}
