<?php

if (!request()->has('term')) {
    return response()->json([]);
}

$searchTerm = request()->post('term');
if (strlen($searchTerm) < 2) {
    return response()->json([]);
}

$results = [];
performSearch($searchTerm, 0, 10, $results);

$dataOut = [];
foreach ($results as $nextRow) {
    $dataOut[] = [
        'label' => $nextRow['Title'] ?? null,
        'mylink' => $nextRow['Target'] ?? null,
        'username' => $nextRow['ID'] ?? null,
    ];
}

return response()->json($dataOut);
