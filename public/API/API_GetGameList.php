<?php

/*
 *  API_GetGameList - returns games for the specified console
 *    i : console id
 *    f : 1=only return games where NumAchievements > 0 (default: 0)
 *    h : 1=also return hashes (default: 0)
 *
 *  array
 *   object     [value]
 *    string     ID                unique identifier of the game
 *    string     Title             title of the game
 *    string     ConsoleID         unique identifier of the console
 *    string     ConsoleName       name of the console
 *    string     ImageIcon         site-relative path to the game's icon image
 *    int        NumAchievements   number of core achievements for the game
 *    int        NumLeaderboards   number of leaderboards for the game
 *    int        Points            total number of points the game's achievements are worth
 *    array      Hashes
 *     string     [value]          RetroAchievements hash associated to the game
 */

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
