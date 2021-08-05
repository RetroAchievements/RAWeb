<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$consoleID = requestInputQuery('i', null, 'integer');
if ($consoleID < 0) {
    echo json_encode(['success' => false]);
    return;
}

$officialFlag = requestInputQuery('f', false, 'boolean');

getGamesList($consoleID, $dataOut, $officialFlag);

echo utf8_encode(json_encode($dataOut));
