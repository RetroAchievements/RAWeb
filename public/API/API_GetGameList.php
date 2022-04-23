<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$consoleID = requestInputQuery('i', null, 'integer');
if ($consoleID < 0) {
    echo json_encode(['success' => false]);
    exit;
}

$withAchievements = requestInputQuery('f', false, 'boolean');
$withHashes = requestInputQuery('h', false, 'boolean');

getGamesListByDev(null, $consoleID, $dataOut, 1, false, $withAchievements ? 0 : 2);

$response = [];
foreach ($dataOut as &$entry) {
    $responseEntry = [
        'Title' => $entry['Title'],
        'ID' => $entry['ID'],
        'ConsoleID' => $entry['ConsoleID'],
        'ConsoleName' => $entry['ConsoleName'],
        'ImageIcon' => $entry['GameIcon'],
        'NumAchievements' => $entry['NumAchievements'] ?? 0,
        'NumLeaderboards' => $entry['NumLBs'] ?? 0,
        'Points' => $entry['MaxPointsAvailable'] ?? 0,
    ];
    settype($responseEntry['NumAchievements'], 'integer');
    settype($responseEntry['NumLeaderboards'], 'integer');
    settype($responseEntry['Points'], 'integer');

    if ($withHashes) {
        $responseEntry['Hashes'] = [];
    }

    $response[] = $responseEntry;
}

if ($withHashes) {
    foreach (getMD5List($consoleID) as $hash => $gameID) {
        foreach ($response as &$entry) {
            if ($entry['ID'] == $gameID) {
                $entry['Hashes'][] = $hash;
                break;
            }
        }
    }
}

echo json_encode($response, JSON_THROW_ON_ERROR);
