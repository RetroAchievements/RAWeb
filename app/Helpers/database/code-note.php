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
    $userId = $user->id;

    return MemoryNote::query()
        ->select([
            'gd.title as GameTitle',
            'gd.image_icon_asset_path as GameIcon',
            's.name as ConsoleName',
            'memory_notes.game_id as GameID',
        ])
        ->selectRaw('COUNT(memory_notes.game_id) as TotalNotes')
        ->selectRaw('SUM(CASE WHEN memory_notes.user_id = ? THEN 1 ELSE 0 END) AS NoteCount', [$userId])
        ->leftJoin('games as gd', 'gd.id', '=', 'memory_notes.game_id')
        ->leftJoin('systems as s', 's.id', '=', 'gd.system_id')
        ->whereRaw('LENGTH(body) > 0')
        ->whereIn('gd.id', function ($query) use ($userId) {
            $query->select('game_id')
                ->distinct()
                ->from('memory_notes')
                ->where('user_id', $userId)
                ->whereNull('deleted_at');
        })
        ->whereNotNull('gd.title')
        ->groupBy('GameID', 'GameTitle')
        ->havingRaw('NoteCount > 0')
        ->orderByDesc('NoteCount')
        ->orderBy('GameTitle')
        ->toBase() // force rows to come back as stdClass
        ->get()
        ->map(fn ($row) => (array) $row)
        ->all();
}
