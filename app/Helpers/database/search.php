<?php

use App\Enums\SearchType;
use Illuminate\Support\Facades\DB;

/**
 * Search users by display name for the legacy autocomplete endpoint. This used to support
 * games, hubs, achievements, forums, and comments, but the only live caller searches users.
 */
function performSearch(
    string $searchQuery,
    int $offset,
    int $count,
    ?array &$searchResultsOut,
): int {
    $likeAnywhere = '%' . $searchQuery . '%';
    $likePrefix = $searchQuery . '%';

    $countRow = DB::select(
        'SELECT COUNT(*) AS Count FROM users WHERE display_name LIKE ? AND Permissions >= 0 AND deleted_at IS NULL',
        [$likeAnywhere],
    );
    $resultCount = (int) ($countRow[0]->Count ?? 0);

    if ($resultCount === 0 || $count <= 0) {
        return $resultCount;
    }

    $query = "
        SELECT " . SearchType::User . " AS Type, ua.display_name AS ID,
            CONCAT('/user/', ua.display_name) AS Target, ua.display_name AS Title,
            CASE WHEN ua.display_name LIKE ? THEN 0 ELSE 1 END AS SecondarySort
        FROM users AS ua
        WHERE ua.display_name LIKE ? AND ua.Permissions >= 0 AND ua.deleted_at IS NULL
        ORDER BY SecondarySort, ua.display_name
        LIMIT $offset, $count";

    foreach (DB::select($query, [$likePrefix, $likeAnywhere]) as $nextData) {
        $searchResultsOut[] = (array) $nextData;
    }

    return $resultCount;
}
