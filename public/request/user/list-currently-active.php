<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$ingameList = getLatestRichPresenceUpdates();

$mergedList = [];

foreach ($ingameList as $playerIngame) {
    // Array merge/overwrite
    $mergedList[$playerIngame['User']] = $playerIngame;
    $mergedList[$playerIngame['User']]['InGame'] = true;
}

header('Content-type: application/json');
echo json_encode(array_values($mergedList), JSON_UNESCAPED_UNICODE);
