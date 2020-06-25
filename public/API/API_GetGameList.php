<?php

require_once __DIR__ . '/../../vendor/autoload.php';

runPublicApiMiddleware();

settype($consoleID, 'integer');
$consoleID = seekGET('i');
if ($consoleID < 0) {
    echo json_encode(['success' => false]);
    return;
}

settype($officialFlag, 'boolean');
$officialFlag = seekGET('f', false);

getGamesList($consoleID, $dataOut, $officialFlag);

echo utf8_encode(json_encode($dataOut));
