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

function getCodeNotes(int $gameId, array &$codeNotesOut): bool
{
    $codeNotesOut = loadCodeNotes($gameId);

    return true;
}

/**
 * Gets the number of code notes created for each game the user has created any notes for.
 */
function getCodeNoteCounts(User $user): array
{
    $userId = $user->ID;

    $retVal = [];
    $query = "SELECT gd.title as GameTitle, gd.image_icon_asset_path as GameIcon, c.Name as ConsoleName, mn.game_id as GameID, COUNT(mn.game_id) as TotalNotes,
              SUM(CASE WHEN mn.user_id = $userId THEN 1 ELSE 0 END) AS NoteCount
              FROM memory_notes AS mn
              LEFT JOIN games AS gd ON gd.id = mn.game_id
              LEFT JOIN Console AS c ON c.ID = gd.system_id
              WHERE LENGTH(body) > 0
              AND mn.deleted_at IS NULL
              AND gd.id IN (SELECT DISTINCT game_id from memory_notes WHERE user_id = $userId AND deleted_at IS NULL)
              AND gd.title IS NOT NULL
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
