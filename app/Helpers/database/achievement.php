<?php

use App\Models\Achievement;
use App\Models\System;
use App\Models\User;
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
        $selectAwardedDate = ", pa.unlocked_effective_at AS AwardedDate";
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
