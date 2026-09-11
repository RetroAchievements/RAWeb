<?php

use App\Models\MemoryNote;
use App\Models\User;

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
