<?php

require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$consoleID = seekGET('i');

getGamesList($consoleID, $dataOut);

echo json_encode(utf8ize($dataOut));
