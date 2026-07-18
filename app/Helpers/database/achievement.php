<?php

use App\Models\Achievement;
use App\Models\System;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
    // We can't run a sort on a user's achievements AwardedDate
    // if we don't have a user. Bail from the sort.
    if (($sortBy == 9 || $sortBy == 19) && !$user) {
        $sortBy = 0;
    }

    // TODO slow query (18)
    $query = DB::table('achievements as ach')
        ->select([
            'ach.id',
            'ach.title as AchievementTitle',
            'ach.description',
            'ach.points',
            'ach.points_weighted as TrueRatio',
            'ach.type',
            'ach.created_at as DateCreated',
            'ach.modified_at as DateModified',
            'ach.image_name as BadgeName',
            'ach.game_id as GameID',
            'gd.title as GameTitle',
            'gd.image_icon_asset_path as GameIcon',
            'gd.system_id as ConsoleID',
            's.name as ConsoleName',
            'ua.username as Author',
        ])
        ->join('users as ua', 'ua.id', '=', 'ach.user_id')
        ->join('games as gd', 'gd.id', '=', 'ach.game_id')
        ->join('systems as s', 's.id', '=', 'gd.system_id')
        ->where('gd.system_id', '!=', System::Events)
        ->where('ach.is_promoted', '=', $isPromoted ?? true)
        ->whereNull('ach.deleted_at');

    if ($params === 1 && isset($user)) {
        // Achievements the user has unlocked.
        $query->join('player_achievements as pa', function ($join) use ($user) {
            $join->on('pa.achievement_id', '=', 'ach.id')
                ->where('pa.user_id', '=', $user->id);
        });
        $query->addSelect('pa.unlocked_effective_at as AwardedDate');
        $query->whereNotNull('pa.unlocked_at');
    } else {
        $query->selectRaw('NULL AS AwardedDate');

        if ($params === 2) {
            // Achievements the user hasn't unlocked.
            $query->whereNotExists(function ($subQuery) use ($user) {
                $subQuery->selectRaw('1')
                    ->from('player_achievements as pa')
                    ->whereColumn('pa.achievement_id', 'ach.id')
                    ->where('pa.user_id', '=', $user->id);
            });
        }
    }

    if ($developer) {
        $query->where('ach.user_id', '=', $developer->id);
    }

    if ($sortBy === 4) {
        $query->where('ach.points_weighted', '>', 0);
    }

    $order = match ($sortBy) {
        0, 1 => 'ach.title',
        2 => 'ach.description',
        3 => 'ach.points, GameTitle',
        4 => 'ach.points_weighted, ach.points DESC, GameTitle',
        5 => 'ua.username',
        6 => 'GameTitle',
        7 => 'ach.created_at',
        8 => 'ach.modified_at',
        9 => 'AwardedDate',
        11 => 'ach.title DESC',
        12 => 'ach.description DESC',
        13 => 'ach.points DESC, GameTitle',
        14 => 'ach.points_weighted DESC, ach.points, GameTitle',
        15 => 'ua.username DESC',
        16 => 'GameTitle DESC',
        17 => 'ach.created_at DESC',
        18 => 'ach.modified_at DESC',
        19 => 'AwardedDate DESC',
        default => 'ach.id',
    };

    $query->orderByRaw($order);

    return $query->offset($offset)
        ->limit($limit)
        ->get()
        ->map(fn ($row) => (array) $row);
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
