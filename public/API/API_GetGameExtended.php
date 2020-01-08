<?php
require_once __DIR__ . '/../../lib/bootstrap.php';

if (!ValidateAPIKey(seekGET('z'), seekGET('y'))) {
    echo "Invalid API Key";
    exit;
}

$gameID = seekGET('i');
getGameMetadata($gameID, null, $achData, $gameData);

foreach ($achData as &$achievement) {
    $achievement['MemAddr'] = md5($achievement['MemAddr'] ?? null);
}
$gameData['Achievements'] = $achData;
$gameData['RichPresencePatch'] = md5($gameData['RichPresencePatch'] ?? null);

echo json_encode($gameData);
