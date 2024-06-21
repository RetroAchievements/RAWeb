<?php

use App\Community\Enums\ArticleType;
use App\Enums\Permissions;
use App\Models\Leaderboard;
use App\Models\LeaderboardEntry;
use App\Models\User;
use App\Platform\Enums\ValueFormat;
use Illuminate\Database\Eloquent\Builder;

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

    $existingLeaderboardEntry = LeaderboardEntry::withTrashed()
        ->where('leaderboard_id', $leaderboard->id)
        ->where('user_id', $user->id)
        ->first();

    if ($existingLeaderboardEntry) {
        if ($existingLeaderboardEntry->trashed()
            || $leaderboard->isBetterScore($newEntry, $existingLeaderboardEntry->score)) {

            if ($existingLeaderboardEntry->trashed()) {
                $existingLeaderboardEntry->restore();
            }

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

    $retVal['RankInfo'] = [
        'NumEntries' => $leaderboard->entries()->count(),
    ];

    $retVal['RankInfo']['Rank'] = $leaderboard->getRank($retVal['BestScore']);
    $entries = $leaderboard->sortedEntries();

    $getEntries = function (Builder $query) {
        $entries = $query
            ->with('user')
            ->limit(10)
            ->get()
            ->map(fn ($entry) => [
                'User' => $entry->user->User,
                'Score' => $entry->score,
                'DateSubmitted' => $entry->updated_at->unix(),
            ])
            ->toArray();

        $index = 1;
        $rank = 0;
        $score = null;
        foreach ($entries as &$entry) {
            if ($entry['Score'] !== $score) {
                $score = $entry['Score'];
                $rank = $index;
            }

            $entry['Rank'] = $rank;
            $index++;
        }

        return $entries;
    };

    $retVal['TopEntries'] = $getEntries($entries->getQuery());

    $retVal['TopEntriesFriends'] = $getEntries($entries->whereHas('user', function ($query) use ($user) {
        $friends = $user->followedUsers()->pluck('related_user_id');
        $friends[] = $user->id;
        $query->whereIn('ID', $friends);
    })->getQuery());

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
    $wasLeaderboardEntryDeleted = $leaderboardEntry->delete();

    return $wasLeaderboardEntryDeleted;
}

function GetLeaderboardData(
    Leaderboard $leaderboard,
    ?User $user,
    int $numToFetch,
    int $offset,
    bool $nearby = false
): array {
    $retVal = [
        'LBID' => $leaderboard->ID,
        'GameID' => $leaderboard->game->ID,
        'GameTitle' => $leaderboard->game->Title,
        'LowerIsBetter' => $leaderboard->LowerIsBetter,
        'LBTitle' => $leaderboard->Title,
        'LBDesc' => $leaderboard->Description,
        'LBFormat' => $leaderboard->Format,
        'LBMem' => $leaderboard->Mem,
        'LBAuthor' => $leaderboard->developer?->User,
        'ConsoleID' => $leaderboard->game->system->id,
        'ConsoleName' => $leaderboard->game->system->name,
        'ForumTopicID' => $leaderboard->game->ForumTopicID,
        'GameIcon' => $leaderboard->game->ImageIcon,
        'LBCreated' => $leaderboard->Created,
        'LBUpdated' => $leaderboard->Updated,
        'TotalEntries' => $leaderboard->entries()->count(),
        'Entries' => [],
    ];

    // If a $user is passed in and $nearby is true then change $offset to give
    // entries around the player based on their index and total entries
    if ($nearby && $user) {
        $entry = getLeaderboardUserEntry($leaderboard, $user);
        if ($entry !== null) {
            $offset = $entry['Index'] - intdiv($numToFetch, 2) - 1;
            if ($offset <= 0) {
                $offset = 0;
            } elseif ($retVal['TotalEntries'] - $entry['Index'] + 1 < $numToFetch) {
                $offset = max(0, $retVal['TotalEntries'] - $numToFetch);
            }
        }
    }

    // Now get entries:
    $index = $rank = $offset + 1;
    $rankScore = null;
    $userFound = false;
    $entries = $leaderboard->sortedEntries()->with('user')->skip($offset)->take($numToFetch);
    foreach ($entries->get() as $entry) {
        if ($entry->score !== $rankScore) {
            if ($rankScore === null) {
                $rank = $leaderboard->getRank($entry->score);
            } else {
                $rank = $index;
            }
            $rankScore = $entry->score;
        }

        $retVal['Entries'][] = [
            'User' => $entry->user->display_name,
            'DateSubmitted' => $entry->updated_at->unix(),
            'Score' => $entry->score,
            'Rank' => $rank,
            'Index' => $index,
        ];

        if ($entry->user->is($user)) {
            $userFound = true;
        }

        $index++;
    }

    if ($userFound === false && $user && !$nearby) {
        $entry = getLeaderboardUserEntry($leaderboard, $user);
        if ($entry) {
            $retVal['Entries'][] = $entry;
        }
    }

    return $retVal;
}

function getLeaderboardUserEntry(Leaderboard $leaderboard, User $user): ?array
{
    $userEntry = $leaderboard->entries(includeUnrankedUsers: true)
        ->where('user_id', '=', $user->id)
        ->first();

    if (!$userEntry) {
        return null;
    }

    $retVal = [
        'User' => $user->display_name,
        'DateSubmitted' => $userEntry->updated_at->unix(),
        'Score' => $userEntry->score,
        'Rank' => $leaderboard->getRank($userEntry->score),
    ];

    $sharedRankEarlierEntryCount = $leaderboard->entries()
        ->where('score', '=', $userEntry->score)
        ->where('updated_at', '<', $userEntry->updated_at)
        ->count();

    $retVal['Index'] = $retVal['Rank'] + $sharedRankEarlierEntryCount;

    return $retVal;
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
            WHERE le.deleted_at IS NULL
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
    $query = "INSERT INTO LeaderboardDef (GameID, Mem, Format, Title, Description, LowerIsBetter, DisplayOrder, author_id, Created)
                                VALUES ($gameID, '$defaultMem', 'SCORE', 'My Leaderboard', 'My Leaderboard Description', 0,
                                (SELECT * FROM (SELECT COALESCE(Max(DisplayOrder) + 1, 0) FROM LeaderboardDef WHERE  GameID = $gameID) AS temp), {$user->id}, NOW())";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $db = getMysqliConnection();
        $lbIDOut = mysqli_insert_id($db);

        return true;
    }

    return false;
}

function UploadNewLeaderboard(
    string $authorUsername,
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
    $originalAuthor = null;

    if ($idInOut > 0) {
        $foundLeaderboard = Leaderboard::find($idInOut);
        if ($foundLeaderboard) {
            $displayOrder = $foundLeaderboard->order_column;
            $originalAuthor = $foundLeaderboard->authorUser;

            $data['DisplayOrder'] = $displayOrder;
            $data['Author'] = $originalAuthor?->display_name ?? "Unknown";
        } else {
            $errorOut = "Unknown leaderboard";

            return false;
        }
    }

    $authorModel = User::firstWhere('User', $authorUsername);

    // Prevent non-developers from uploading or modifying leaderboards
    $userPermissions = (int) $authorModel->getAttribute('Permissions');
    if ($userPermissions < Permissions::Developer) {
        if (
            $userPermissions < Permissions::JuniorDeveloper
            || (isset($originalAuthor) && !$authorModel->is($originalAuthor))
        ) {
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

        $foundLeaderboard = Leaderboard::find($idInOut);
        if ($foundLeaderboard) {
            $displayOrder = $foundLeaderboard->order_column;
            $data['DisplayOrder'] = $displayOrder;
        }
    }

    if (!submitLBData($authorUsername, $idInOut, $mem, $title, $desc, $format, $lowerIsBetter, $displayOrder)) {
        $errorOut = "Internal error updating leaderboard.";

        return false;
    }

    if (isset($originalAuthor)) {
        addArticleComment("Server", ArticleType::Leaderboard, $idInOut,
            "{$authorModel->display_name} edited this leaderboard.", $authorModel->username
        );
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
