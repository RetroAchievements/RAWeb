<?php

use RA\SearchType;

if (!request()->has('term')) {
    return response()->json([]);
}

$searchTerm = request()->post('term');
if (strlen($searchTerm) < 2) {
    return response()->json([]);
}
sanitize_sql_inputs($searchTerm);

$source = request()->post('source');

$maxResults = 10;
$permissions = 0; /* permissions only needed for searching forums */

$results = [];
if ($source == 'game') {
    $order = [SearchType::Game];
} elseif ($source == 'achievement') {
    $order = [SearchType::Achievement];
} elseif ($source == 'user' || $source == 'game-compare') {
    $order = [SearchType::User];
} else {
    $order = [SearchType::Game, SearchType::Achievement, SearchType::User];
}

$numFound = 0;
foreach ($order as $searchType) {
    $numFound += performSearch($searchType, $searchTerm, 0, $maxResults, $permissions, $results);
    if ($numFound >= $maxResults) {
        break;
    }
}

$dataOut = [];
foreach ($results as $nextRow) {
    $dataOut[] = [
        'label' => $nextRow['Title'] ?? null,
        'id' => $nextRow['ID'] ?? null,
        'mylink' => $nextRow['Target'] ?? null,
        'category' => $nextRow['Type'],
    ];
}

return response()->json($dataOut);
