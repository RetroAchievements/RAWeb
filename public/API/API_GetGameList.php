<?php

require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

settype($consoleID, 'integer');
$consoleID = seekGET('i');
if ($consoleID < 0) {
    echo json_encode(['success' => false]);
    return;
}

getGamesList($consoleID, $dataOut);

echo utf8_encode(json_encode($dataOut));
