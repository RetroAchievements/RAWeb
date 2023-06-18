<?php

use App\Platform\Models\MemoryNote;
use App\Site\Enums\Permissions;
use App\Site\Models\User;

function getCodeNotesData(int $gameID): array
{
    $codeNotesOut = [];

    sanitize_sql_inputs($gameID);

    $query = "SELECT ua.User, cn.Address, cn.Note
              FROM CodeNotes AS cn
              LEFT JOIN UserAccounts AS ua ON ua.ID = cn.AuthorID
              WHERE cn.GameID = '$gameID'
              ORDER BY cn.Address ";

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

function getCodeNotes(int $gameID, ?array &$codeNotesOut): bool
{
    sanitize_sql_inputs($gameID);

    $query = "SELECT ua.User, cn.Address, cn.Note
              FROM CodeNotes AS cn
              LEFT JOIN UserAccounts AS ua ON ua.ID = cn.AuthorID
              WHERE cn.GameID = $gameID
              ORDER BY cn.Address ";

    $dbResult = s_mysql_query($query);
    if ($dbResult !== false) {
        $codeNotesOut = [];

        $numResults = 0;
        while ($db_entry = mysqli_fetch_assoc($dbResult)) {
            // Seamless :)
            $db_entry['Address'] = sprintf("0x%06x", $db_entry['Address']);
            $codeNotesOut[$numResults++] = $db_entry;
        }

        return true;
    }
    log_sql_fail();

    return false;
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
        && $currentNotes[$i]['User'] !== $user
        && !empty($currentNotes[$i]['Note'])
    ) {
        return false;
    }

    MemoryNote::updateOrCreate(
        [
            'GameID' => $gameID,
            'Address' => $address,
        ],
        [
            'AuthorID' => $user->ID,
            'Note' => $note,
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
    $query = "SELECT gd.Title as GameTitle, gd.ImageIcon as GameIcon, c.Name as ConsoleName, cn.GameID as GameID, COUNT(cn.GameID) as TotalNotes,
              SUM(CASE WHEN cn.AuthorID = $userId THEN 1 ELSE 0 END) AS NoteCount
              FROM CodeNotes AS cn
              LEFT JOIN GameData AS gd ON gd.ID = cn.GameID
              LEFT JOIN Console AS c ON c.ID = gd.ConsoleID
              WHERE LENGTH(Note) > 0
              AND gd.ID IN (SELECT GameID from CodeNotes WHERE AuthorID = $userId GROUP BY GameID)
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
