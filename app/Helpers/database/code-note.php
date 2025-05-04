<?php

use App\Models\MemoryNote;
use App\Models\User;

function loadCodeNotes(int $gameId): array
{
    $codeNotes = MemoryNote::query()
        ->with(['user' => function ($query) {
            $query->withTrashed();
        }])
        ->where('game_id', $gameId)
        ->orderBy('address')
        ->get()
        ->map(function ($note) {
            return [
                'User' => $note->user->display_name,
                'Address' => $note->address_hex,
                'Note' => $note->body,
            ];
        })
        ->toArray();

    return empty($codeNotes) ? [] : $codeNotes;
}

function getCodeNotes(int $gameId, ?array &$codeNotesOut): bool
{
    $codeNotesOut = loadCodeNotes($gameId);

    return $codeNotesOut !== null;
}

/**
 * Gets the number of code notes created for each game the user has created any notes for.
 */
function getCodeNoteCounts(User $user): array
{
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
