<?php

use App\Enums\Permissions;
use App\Models\MemoryNote;
use App\Models\User;

function getCodeNotesData(int $gameID): array
{
    $codeNotesOut = [];

    $query = "SELECT ua.User, mn.address AS Address, mn.body AS Note
              FROM memory_notes AS mn
              LEFT JOIN UserAccounts AS ua ON ua.ID = mn.user_id
              WHERE mn.GameID = '$gameID'
              ORDER BY mn.address ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            // Seamless :)
            $db_entry['Address'] = sprintf("0x%06x", $db_entry['Address']);
            $codeNotesOut[] = $db_entry;
        }
    } else {
        log_sql_fail();
    }

    return $codeNotesOut;
}

function submitCodeNote2(string $username, int $gameID, int $address, string $body): bool
{
    /** @var ?User $user */
    $user = User::firstWhere('User', $username);

    if (!$user) {
        return false;
    }

    // TODO use a policy
    $permissions = (int) $user->getAttribute('Permissions');

    // Prevent <= registered users from creating code notes.
    if ($permissions <= Permissions::Registered) {
        return false;
    }

    $addressHex = '0x' . str_pad(dechex($address), 6, '0', STR_PAD_LEFT);
    $currentNotes = getCodeNotesData($gameID);
    $i = array_search($addressHex, array_column($currentNotes, 'Address'));

    // TODO use a policy
    if (
        $i !== false
        && $permissions <= Permissions::JuniorDeveloper
        && $currentNotes[$i]['User'] !== $user->username
        && !empty($currentNotes[$i]['Note'])
    ) {
        return false;
    }

    MemoryNote::updateOrCreate(
        [
            'GameID' => $gameID,
            'address' => $address,
        ],
        [
            'user_id' => $user->id,
            'body' => $body,
        ]
    );

    return true;
}

/**
 * Gets the number of code notes created for each game the user has created any notes for.
 */
function getCodeNoteCounts(User $user): array
{
    $userId = $user->id;

    $retVal = [];
    $query = "SELECT gd.Title as GameTitle, gd.ImageIcon as GameIcon, c.Name as ConsoleName, mn.GameID as GameID, COUNT(mn.GameID) as TotalNotes,
              SUM(CASE WHEN mn.user_id = $userId THEN 1 ELSE 0 END) AS NoteCount
              FROM memory_notes AS mn
              LEFT JOIN GameData AS gd ON gd.ID = mn.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE LENGTH(body) > 0
              AND gd.ID IN (SELECT GameID from memory_notes WHERE user_id = $userId GROUP BY GameID)
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
