<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$gameID = requestInputQuery('i');
getGameMetadata($gameID, null, $achData, $gameData);

foreach ($achData as &$achievement) {
    $achievement['MemAddr'] = md5($achievement['MemAddr'] ?? null);
}
$gameData['Achievements'] = $achData;
$gameData['RichPresencePatch'] = md5($gameData['RichPresencePatch'] ?? null);

echo json_encode($gameData);
