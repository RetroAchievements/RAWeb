<?php

$ingameList = getLatestRichPresenceUpdates();

$mergedList = [];

foreach ($ingameList as $playerIngame) {
    // Array merge/overwrite
    $mergedList[$playerIngame['User']] = $playerIngame;
    $mergedList[$playerIngame['User']]['InGame'] = true;
}

return response()->json(array_values($mergedList));
