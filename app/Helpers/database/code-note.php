<?php

use App\Enums\Permissions;
use App\Models\MemoryNote;
use App\Models\User;

function loadCodeNotes(int $gameId): ?array
{
    $query = "SELECT ua.User, mn.address AS Address, mn.body AS Note
              FROM memory_notes AS mn
              LEFT JOIN UserAccounts AS ua ON ua.ID = mn.user_id
              WHERE mn.game_id = '$gameId'
              ORDER BY mn.Address ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $codeNotesOut = [];

        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            // Seamless :)
            $db_entry['Address'] = sprintf("0x%06x", $db_entry['Address']);
            $codeNotesOut[] = $db_entry;
        }

        return $codeNotesOut;
    }

    log_sql_fail();

    return null;
}

function getCodeNotesData(int $gameId): array
{
    $codeNotesOut = loadCodeNotes($gameId);

    return $codeNotesOut !== null ? $codeNotesOut : [];
}

function getCodeNotes(int $gameId, ?array &$codeNotesOut): bool
{
    $codeNotesOut = loadCodeNotes($gameId);

    return $codeNotesOut !== null;
}

function submitCodeNote2(string $username, int $gameID, int $address, string $note): bool
{
    /** @var ?User $user */
    $user = User::firstWhere('User', $username);

    if (!$user) {
        return false;
    }

    // TODO refactor to ability
    $permissions = (int) $user->getAttribute('Permissions');

    // Prevent <= registered users from creating code notes.
    if ($permissions <= Permissions::Registered) {
        return false;
    }

    $addressHex = '0x' . str_pad(dechex($address), 6, '0', STR_PAD_LEFT);
    $currentNotes = getCodeNotesData($gameID);
    $i = array_search($addressHex, array_column($currentNotes, 'Address'));

    if (
        $i !== false
        && $permissions <= Permissions::JuniorDeveloper
        && $currentNotes[$i]['User'] !== $user->User
        && !empty($currentNotes[$i]['Note'])
    ) {
        return false;
    }

    MemoryNote::updateOrCreate(
        [
            'game_id' => $gameID,
            'address' => $address,
        ],
        [
            'user_id' => $user->ID,
            'body' => $note,
        ]
    );

    return true;
}

/**
 * Gets the number of code notes created for each game the user has created any notes for.
 */
function getCodeNoteCounts(string $username): array
{
    /** @var ?User $user */
    $user = User::firstWhere('User', $username);
    $userId = $user->ID;

    $retVal = [];
    $query = "SELECT gd.Title as GameTitle, gd.ImageIcon as GameIcon, c.Name as ConsoleName, mn.game_id as GameID, COUNT(mn.game_id) as TotalNotes,
              SUM(CASE WHEN mn.user_id = $userId THEN 1 ELSE 0 END) AS NoteCount
              FROM memory_notes AS mn
              LEFT JOIN GameData AS gd ON gd.ID = mn.game_id
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE LENGTH(body) > 0
              AND gd.ID IN (SELECT game_id from memory_notes WHERE user_id = $userId GROUP BY game_id)
              AND gd.Title IS NOT NULL
              GROUP BY GameID, GameTitle
              HAVING NoteCount > 0
              ORDER BY NoteCount DESC, GameTitle";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            $retVal[] = $db_entry;
        }
    }

    return $retVal;
}
