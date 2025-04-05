<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\SyncAchievementSetOrderColumnsFromDisplayOrdersAction;
use App\Platform\Actions\SyncEventAchievementMetadataAction;
use App\Platform\Actions\UpsertTriggerVersionAction;
use App\Platform\Enums\AchievementAuthorTask;
use App\Platform\Enums\AchievementFlag;
use App\Platform\Enums\AchievementPoints;
use App\Platform\Enums\AchievementType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Facades\CauserResolver;

/**
 * @return Collection<int, array>
 */
function getAchievementsList(
    ?User $user,
    int $sortBy,
    int $params,
    int $limit,
    int $offset,
    ?AchievementFlag $achievementFlag = null,
    ?User $developer = null
): Collection {
    $bindings = [
        'offset' => $offset,
        'limit' => $limit,
        'achievementFlag' => $achievementFlag?->value ?? AchievementFlag::OfficialCore->value,
    ];

    $selectAwardedDate = ", NULL AS AwardedDate";
    $joinPlayerAchievements = "";
    $additionalWhereClauses = "";
    $notExistsSubquery = "";

    // We can't run a sort on a user's achievements AwardedDate
    // if we don't have a user. Bail from the sort.
    if (($sortBy == 9 || $sortBy == 19) && !$user) {
        $sortBy = 0;
    }

    if ($params === 1 && isset($user)) {
        // Achievements the user has unlocked.
        $bindings['userId'] = $user->id;

        $joinPlayerAchievements = "INNER JOIN player_achievements AS pa ON pa.achievement_id = ach.ID AND pa.user_id = :userId";
        $selectAwardedDate = ", COALESCE(pa.unlocked_hardcore_at, pa.unlocked_at) AS AwardedDate";
        $additionalWhereClauses .= "AND pa.unlocked_at IS NOT NULL ";
    } elseif ($params === 2) {
        // Achievements the user hasn't unlocked.
        $bindings['userId'] = $user->id;

        $notExistsSubquery = "AND NOT EXISTS (
            SELECT 1 FROM player_achievements pa
            WHERE pa.achievement_id = ach.ID AND pa.user_id = :userId
        ) ";
    }

    // TODO slow query (18)
    $query = "SELECT
                ach.ID,
                ach.Title AS AchievementTitle,
                ach.Description,
                ach.Points,
                ach.TrueRatio,
                ach.type,
                ach.DateCreated,
                ach.DateModified,
                ach.BadgeName,
                ach.GameID,
                gd.Title AS GameTitle,
                gd.ImageIcon AS GameIcon,
                gd.ConsoleID,
                c.Name AS ConsoleName,
                ua.User AS Author
                $selectAwardedDate
            FROM Achievements AS ach
            $joinPlayerAchievements
            INNER JOIN UserAccounts AS ua ON ua.ID = ach.user_id
            INNER JOIN GameData AS gd ON gd.ID = ach.GameID
            INNER JOIN Console AS c ON c.ID = gd.ConsoleID
            WHERE gd.ConsoleID != " . System::Events . "
            AND ach.Flags = :achievementFlag ";

    if ($developer) {
        $bindings['developerId'] = $developer->id;
        $query .= "AND ach.user_id = :developerId ";
    }

    if ($sortBy === 4) {
        $query .= "AND ach.TrueRatio > 0 ";
    }

    $query .= $additionalWhereClauses;

    if (!empty($notExistsSubquery)) {
        $query .= $notExistsSubquery;
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
            $query .= "ORDER BY ua.User ";
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
            $query .= "ORDER BY ua.User DESC ";
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
        default:
            $query .= "ORDER BY ach.ID ";
            break;
    }

    $query .= "LIMIT :offset, :limit";

    return legacyDbFetchAll($query, $bindings);
}

function GetAchievementData(int $achievementId): ?array
{
    $achievement = Achievement::find($achievementId);

    if (!$achievement) {
        return null;
    }

    return [
        'ID' => $achievement->id,
        'AchievementID' => $achievement->id,
        'GameID' => $achievement->game->id,
        'Title' => $achievement->title,
        'AchievementTitle' => $achievement->title,
        'Description' => $achievement->description,
        'Points' => $achievement->points,
        'TrueRatio' => $achievement->points_weighted,
        'Flags' => $achievement->Flags,
        'type' => $achievement->type,
        'Author' => $achievement->developer?->display_name,
        'AuthorULID' => $achievement->developer?->ulid,
        'DateCreated' => $achievement->DateCreated->format('Y-m-d H:i:s'),
        'DateModified' => $achievement->DateModified->format('Y-m-d H:i:s'),
        'BadgeName' => $achievement->badge_name,
        'DisplayOrder' => $achievement->DisplayOrder,
        'AssocVideo' => $achievement->AssocVideo,
        'MemAddr' => $achievement->MemAddr,
        'ConsoleID' => $achievement->game->system->id,
        'ConsoleName' => $achievement->game->system->name,
        'GameTitle' => $achievement->game->title,
        'GameIcon' => $achievement->game->ImageIcon,
    ];
}

function UploadNewAchievement(
    string $authorUsername,
    int $gameID,
    string $title,
    string $desc,
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

    $author = User::whereName($authorUsername)->first();
    $authorPermissions = (int) $author?->getAttribute('Permissions');

    // Prevent <= registered users from uploading or modifying achievements
    if ($authorPermissions < Permissions::JuniorDeveloper) {
        $errorOut = "You must be a developer to perform this action! Please drop a message in the forums to apply.";

        return false;
    }

    if (!AchievementFlag::tryFrom($flag)) {
        $errorOut = "Invalid achievement flag";

        return false;
    }

    if ($flag === AchievementFlag::OfficialCore->value && !isValidConsoleId($consoleID)) {
        $errorOut = "You cannot promote achievements for a game from an unsupported console (console ID: " . $consoleID . ").";

        return false;
    }

    if (!AchievementPoints::isValid($points)) {
        $errorOut = "Invalid points value (" . $points . ").";

        return false;
    }

    if ($type !== null && $type !== 'not-given') {
        if (!AchievementType::isValid($type)) {
            $errorOut = "Invalid achievement type";

            return false;
        }

        $isForSubsetOrTestKit = (
            mb_strpos($gameData['Title'], '[Subset') !== false
            || mb_strpos($gameData['Title'], '~Test Kit~') !== false
        );
        $isEventGame = $gameData['ConsoleID'] == 101;

        if (
            ($isForSubsetOrTestKit || $isEventGame)
            && ($type === AchievementType::Progression || $type === AchievementType::WinCondition)
        ) {
            $errorOut = "Cannot set progression or win condition type on achievement in subset, test kit, or event.";

            return false;
        }
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
        $achievement->user_id = $author->id;
        $achievement->BadgeName = $badge;

        CauserResolver::setCauser($author);

        $achievement->save();
        $idInOut = $achievement->ID;

        // It's a new achievement, so create the initial trigger version.
        (new UpsertTriggerVersionAction())->execute(
            $achievement,
            $mem,
            versioned: $flag === AchievementFlag::OfficialCore->value,
            user: $author
        );

        $achievement->ensureAuthorshipCredit($author, AchievementAuthorTask::Logic);

        static_addnewachievement($idInOut);
        addArticleComment(
            "Server",
            ArticleType::Achievement,
            $idInOut,
            "{$author->display_name} uploaded this achievement.",
            $author->display_name
        );

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

        if ($flag === AchievementFlag::OfficialCore->value || $changingAchSet) { // If modifying core or changing achievement state
            // changing ach set detected; user is $author, permissions is $authorPermissions, target set is $flag

            // Only allow jr. devs to modify core achievements if they are the author and not updating logic or state
            // TODO use a policy
            if ($authorPermissions < Permissions::Developer && ($changingLogic || $changingAchSet || $achievement->user_id !== $author->id)) {
                // Must be developer to modify core logic!
                $errorOut = "You must be a developer to perform this action! Please drop a message in the forums to apply.";

                return false;
            }
        }

        if ($flag === AchievementFlag::Unofficial->value) { // If modifying unofficial
            // Only allow jr. devs to modify unofficial if they are the author
            // TODO use a policy
            if ($authorPermissions == Permissions::JuniorDeveloper && $achievement->user_id !== $author->id) {
                $errorOut = "You must be a developer to perform this action! Please drop a message in the forums to apply.";

                return false;
            }
        }

        if ($achievement->isDirty()) {
            CauserResolver::setCauser($author);

            (new SyncEventAchievementMetadataAction())->execute($achievement);

            $achievement->DateModified = now();
            $achievement->save();

            static_setlastupdatedgame($gameID);
            static_setlastupdatedachievement($idInOut);

            if ($changingLogic) {
                $achievement->ensureAuthorshipCredit($author, AchievementAuthorTask::Logic);

                (new UpsertTriggerVersionAction())->execute(
                    $achievement,
                    $achievement->MemAddr,
                    versioned: $achievement->Flags === AchievementFlag::OfficialCore->value,
                    user: $author
                );
            } elseif ($changingAchSet && $achievement->trigger && $achievement->Flags === AchievementFlag::OfficialCore->value) {
                // If only flags changed, re-version the existing trigger (if it exists).
                (new UpsertTriggerVersionAction())->execute(
                    $achievement,
                    $achievement->trigger->conditions,
                    versioned: true,
                    user: $author
                );
            }

            if ($changingAchSet) {
                if ($flag === AchievementFlag::OfficialCore->value) {
                    addArticleComment(
                        "Server",
                        ArticleType::Achievement,
                        $idInOut,
                        "{$author->display_name} promoted this achievement to the Core set.",
                        $author->display_name
                    );
                } elseif ($flag === AchievementFlag::Unofficial->value) {
                    addArticleComment(
                        "Server",
                        ArticleType::Achievement,
                        $idInOut,
                        "{$author->display_name} demoted this achievement to Unofficial.",
                        $author->display_name
                    );
                }
                expireGameTopAchievers($gameID);
            } else {
                $editString = implode(', ', $fields);

                if (!empty($editString)) {
                    addArticleComment(
                        "Server",
                        ArticleType::Achievement,
                        $idInOut,
                        "{$author->display_name} edited this achievement's $editString.",
                        $author->display_name
                    );
                }
            }
        }

        return true;
    }

    return false;
}

function updateAchievementDisplayOrder(int $achievementId, int $newDisplayOrder): bool
{
    $achievement = Achievement::find($achievementId);

    if (!$achievement) {
        return false;
    }

    $achievement->DisplayOrder = $newDisplayOrder;
    $achievement->save();

    // Double write to achievement_set_achievements to ensure it remains in sync.
    (new SyncAchievementSetOrderColumnsFromDisplayOrdersAction())->execute($achievement);

    return true;
}

function updateAchievementFlag(int|string|array $inputAchievementIds, AchievementFlag $newFlag): void
{
    $achievementIds = is_array($inputAchievementIds) ? $inputAchievementIds : [$inputAchievementIds];

    $achievements = Achievement::whereIn('ID', $achievementIds)
        ->where('Flags', '!=', $newFlag->value)
        ->get();

    foreach ($achievements as $achievement) {
        $achievement->Flags = $newFlag->value;
        $achievement->save();
    }
}

function updateAchievementType(int|string|array $inputAchievementIds, ?string $newType): void
{
    $achievementIds = is_array($inputAchievementIds) ? $inputAchievementIds : [$inputAchievementIds];

    $achievements = Achievement::whereIn('ID', $achievementIds)->get();

    foreach ($achievements as $achievement) {
        $achievement->type = $newType;
        $achievement->Updated = Carbon::now();
        $achievement->save();
    }
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
