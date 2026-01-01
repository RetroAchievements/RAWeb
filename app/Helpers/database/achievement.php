<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\Achievement;
use App\Models\GameAchievementSet;
use App\Models\PlayerGame;
use App\Models\System;
use App\Models\User;
use App\Platform\Actions\SyncAchievementSetOrderColumnsFromDisplayOrdersAction;
use App\Platform\Actions\SyncEventAchievementMetadataAction;
use App\Platform\Actions\UpsertTriggerVersionAction;
use App\Platform\Enums\AchievementAuthorTask;
use App\Platform\Enums\AchievementPoints;
use App\Platform\Enums\AchievementType;
use App\Platform\Jobs\UpdatePlayerGameMetricsJob;
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
    ?bool $isPromoted = null,
    ?User $developer = null,
): Collection {
    $bindings = [
        'offset' => $offset,
        'limit' => $limit,
        'isPromoted' => $isPromoted ?? true,
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

        $joinPlayerAchievements = "INNER JOIN player_achievements AS pa ON pa.achievement_id = ach.id AND pa.user_id = :userId";
        $selectAwardedDate = ", COALESCE(pa.unlocked_hardcore_at, pa.unlocked_at) AS AwardedDate";
        $additionalWhereClauses .= "AND pa.unlocked_at IS NOT NULL ";
    } elseif ($params === 2) {
        // Achievements the user hasn't unlocked.
        $bindings['userId'] = $user->id;

        $notExistsSubquery = "AND NOT EXISTS (
            SELECT 1 FROM player_achievements pa
            WHERE pa.achievement_id = ach.id AND pa.user_id = :userId
        ) ";
    }

    // TODO slow query (18)
    $query = "SELECT
                ach.id,
                ach.title AS AchievementTitle,
                ach.description,
                ach.points,
                ach.points_weighted AS TrueRatio,
                ach.type,
                ach.created_at AS DateCreated,
                ach.modified_at AS DateModified,
                ach.image_name AS BadgeName,
                ach.game_id AS GameID,
                gd.title AS GameTitle,
                gd.image_icon_asset_path AS GameIcon,
                gd.system_id AS ConsoleID,
                s.name AS ConsoleName,
                ua.username AS Author
                $selectAwardedDate
            FROM achievements AS ach
            $joinPlayerAchievements
            INNER JOIN users AS ua ON ua.id = ach.user_id
            INNER JOIN games AS gd ON gd.id = ach.game_id
            INNER JOIN systems AS s ON s.id = gd.system_id
            WHERE gd.system_id != " . System::Events . "
            AND ach.is_promoted = :isPromoted
            AND ach.deleted_at IS NULL ";

    if ($developer) {
        $bindings['developerId'] = $developer->id;
        $query .= "AND ach.user_id = :developerId ";
    }

    if ($sortBy === 4) {
        $query .= "AND ach.points_weighted > 0 ";
    }

    $query .= $additionalWhereClauses;

    if (!empty($notExistsSubquery)) {
        $query .= $notExistsSubquery;
    }

    switch ($sortBy) {
        case 0:
        case 1:
            $query .= "ORDER BY ach.title ";
            break;
        case 2:
            $query .= "ORDER BY ach.description ";
            break;
        case 3:
            $query .= "ORDER BY ach.points, GameTitle ";
            break;
        case 4:
            $query .= "ORDER BY ach.points_weighted, ach.points DESC, GameTitle ";
            break;
        case 5:
            $query .= "ORDER BY ua.username ";
            break;
        case 6:
            $query .= "ORDER BY GameTitle ";
            break;
        case 7:
            $query .= "ORDER BY ach.created_at ";
            break;
        case 8:
            $query .= "ORDER BY ach.modified_at ";
            break;
        case 9:
            $query .= "ORDER BY AwardedDate ";
            break;
        case 11:
            $query .= "ORDER BY ach.title DESC ";
            break;
        case 12:
            $query .= "ORDER BY ach.description DESC ";
            break;
        case 13:
            $query .= "ORDER BY ach.points DESC, GameTitle ";
            break;
        case 14:
            $query .= "ORDER BY ach.points_weighted DESC, ach.points, GameTitle ";
            break;
        case 15:
            $query .= "ORDER BY ua.username DESC ";
            break;
        case 16:
            $query .= "ORDER BY GameTitle DESC ";
            break;
        case 17:
            $query .= "ORDER BY ach.created_at DESC ";
            break;
        case 18:
            $query .= "ORDER BY ach.modified_at DESC ";
            break;
        case 19:
            $query .= "ORDER BY AwardedDate DESC ";
            break;
        default:
            $query .= "ORDER BY ach.id ";
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
        'Flags' => $achievement->is_promoted ? Achievement::FLAG_PROMOTED : Achievement::FLAG_UNPROMOTED,
        'type' => $achievement->type,
        'Author' => $achievement->developer?->display_name,
        'AuthorULID' => $achievement->developer?->ulid,
        'DateCreated' => $achievement->created_at->format('Y-m-d H:i:s'),
        'DateModified' => $achievement->modified_at->format('Y-m-d H:i:s'),
        'BadgeName' => $achievement->image_name,
        'DisplayOrder' => $achievement->order_column,
        'AssocVideo' => $achievement->embed_url,
        'MemAddr' => $achievement->trigger_definition,
        'ConsoleID' => $achievement->game->system->id,
        'ConsoleName' => $achievement->game->system->name,
        'GameTitle' => $achievement->game->title,
        'GameIcon' => $achievement->game->image_icon_asset_path,
    ];
}

function UploadNewAchievement(
    string $authorUsername,
    ?int $gameID,
    string $title,
    string $desc,
    int $points,
    ?string $type,
    string $mem,
    int $flag,
    ?int &$idInOut,
    string $badge,
    ?string &$errorOut,
    ?int $gameAchievementSetID,
): bool {
    if (!$gameAchievementSetID && !$gameID) {
        $errorOut = "You must provide a game ID or a game achievement set ID.";

        return false;
    }

    if ($gameAchievementSetID) {
        $gameAchievementSet = GameAchievementSet::find($gameAchievementSetID);
        if (!$gameAchievementSet) {
            $errorOut = "Game achievement set not found.";

            return false;
        }

        $gameID = $gameAchievementSet->game_id;
    }

    $gameData = getGameData($gameID);
    if (is_null($gameData)) {
        $errorOut = "Game not found.";

        return false;
    }

    $consoleID = $gameData['ConsoleID'];
    $isEventGame = $gameData['ConsoleID'] == System::Events;

    $author = User::whereName($authorUsername)->first();
    $authorPermissions = (int) $author?->getAttribute('Permissions');

    // Prevent <= registered users from uploading or modifying achievements.
    if ($authorPermissions < Permissions::JuniorDeveloper) {
        $errorOut = "You must be a developer to perform this action! Please drop a message in the forums to apply.";

        return false;
    }

    $isPromoted = $flag === Achievement::FLAG_PROMOTED;
    if ($flag !== Achievement::FLAG_PROMOTED && $flag !== Achievement::FLAG_UNPROMOTED) {
        $errorOut = "Invalid achievement flag";

        return false;
    }

    if ($isPromoted && !isValidConsoleId($consoleID)) {
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
            mb_strpos($gameData['title'], '[Subset') !== false
            || mb_strpos($gameData['title'], '~Test Kit~') !== false
        );

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
        $achievement->game_id = $gameID;
        $achievement->title = $title;
        $achievement->description = $desc;
        $achievement->trigger_definition = $mem;
        $achievement->points = $points;
        $achievement->is_promoted = $isPromoted;
        $achievement->type = ($typeValue == 'NULL') ? null : $type;
        $achievement->user_id = $author->id;
        $achievement->image_name = $badge;
        $achievement->modified_at = now();

        CauserResolver::setCauser($author);

        $achievement->save();
        $idInOut = $achievement->id;

        // It's a new achievement, so create the initial trigger version.
        (new UpsertTriggerVersionAction())->execute(
            $achievement,
            $mem,
            versioned: $isPromoted,
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

        $changingPoints = ($achievement->points != $points);
        if ($changingPoints) {
            $achievement->points = $points;
            $fields[] = "points";
        }

        if ($achievement->image_name !== $badge) {
            $achievement->image_name = $badge;
            $fields[] = "badge";
        }

        if ($achievement->title !== $title) {
            $achievement->title = $title;
            $fields[] = "title";
        }

        if ($achievement->description !== $desc) {
            $achievement->description = $desc;
            $fields[] = "description";
        }

        $recalculateBeatTimes = false;
        $changingType = ($achievement->type != $type && $type !== 'not-given');
        if ($changingType) {
            // if changing to/from Progression/WinCondition, recalculate all beat times
            $recalculateBeatTimes = AchievementType::isProgression($type) || AchievementType::isProgression($achievement->type);

            $achievement->type = $type;
            $fields[] = "type";
        }

        $changingLogic = ($achievement->trigger_definition != $mem);
        if ($changingLogic) {
            $achievement->trigger_definition = $mem;
            $fields[] = "logic";
        }

        $changingPromotedStatus = ($achievement->is_promoted != $isPromoted);
        if ($changingPromotedStatus) {
            $achievement->is_promoted = $isPromoted;
        }

        if ($isPromoted || $changingPromotedStatus) { // If modifying core or changing achievement state.
            // changing ach set detected; user is $author, permissions is $authorPermissions, target set is $flag

            // Only allow jr. devs to modify core achievements if they are the author and not updating logic or state
            // TODO use a policy
            if ($authorPermissions < Permissions::Developer && ($changingLogic || $changingPromotedStatus || $achievement->user_id !== $author->id)) {
                // Must be developer to modify core logic!
                $errorOut = "You must be a developer to perform this action! Please drop a message in the forums to apply.";

                return false;
            }
        }

        if (!$isPromoted) { // If modifying unofficial
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

            $achievement->modified_at = now();
            $achievement->save();

            static_setlastupdatedgame($gameID);
            static_setlastupdatedachievement($idInOut);

            if ($changingLogic) {
                $achievement->ensureAuthorshipCredit($author, AchievementAuthorTask::Logic);

                (new UpsertTriggerVersionAction())->execute(
                    $achievement,
                    $achievement->trigger_definition,
                    versioned: $achievement->is_promoted,
                    user: $author
                );
            } elseif ($changingPromotedStatus && $achievement->trigger && $achievement->is_promoted) {
                // If only published status changed, re-version the existing trigger (if it exists).
                (new UpsertTriggerVersionAction())->execute(
                    $achievement,
                    $achievement->trigger->conditions,
                    versioned: true,
                    user: $author
                );
            }

            if ($changingPromotedStatus) {
                if ($isPromoted) {
                    addArticleComment(
                        "Server",
                        ArticleType::Achievement,
                        $idInOut,
                        "{$author->display_name} promoted this achievement to the Core set.",
                        $author->display_name
                    );
                } else {
                    addArticleComment(
                        "Server",
                        ArticleType::Achievement,
                        $idInOut,
                        "{$author->display_name} demoted this achievement to Unofficial.",
                        $author->display_name
                    );
                }
                expireGameTopAchievers($gameID);

                // if promoting/demoting a progression achievement, we need to recalculate beat times
                $recalculateBeatTimes |= AchievementType::isProgression($achievement->type);
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

            if ($recalculateBeatTimes) {
                // changing the type of an achievement or promoting/demoting it can affect
                // the time to beat a game. recalculate them for anyone who has beaten the game.
                $affectedUserIds = PlayerGame::query()
                    ->where('game_id', $achievement->game_id)
                    ->whereNotNull('beaten_at')
                    ->select(['user_id'])
                    ->pluck('user_id');
                foreach ($affectedUserIds as $userId) {
                    dispatch(new UpdatePlayerGameMetricsJob($userId, $achievement->game_id));
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

    $achievement->order_column = $newDisplayOrder;
    $achievement->save();

    // Double write to achievement_set_achievements to ensure it remains in sync.
    (new SyncAchievementSetOrderColumnsFromDisplayOrdersAction())->execute($achievement);

    return true;
}

function updateAchievementPromotedStatus(int|string|array $inputAchievementIds, bool $isPromoted): void
{
    $achievementIds = is_array($inputAchievementIds) ? $inputAchievementIds : [$inputAchievementIds];

    $achievements = Achievement::whereIn('id', $achievementIds)
        ->where('is_promoted', '!=', $isPromoted)
        ->get();

    foreach ($achievements as $achievement) {
        $achievement->is_promoted = $isPromoted;
        $achievement->save();
    }
}

function updateAchievementType(int|string|array $inputAchievementIds, ?string $newType): void
{
    $achievementIds = is_array($inputAchievementIds) ? $inputAchievementIds : [$inputAchievementIds];

    $achievements = Achievement::whereIn('id', $achievementIds)->get();

    foreach ($achievements as $achievement) {
        $achievement->type = $newType;
        $achievement->updated_at = Carbon::now();
        $achievement->save();
    }
}
