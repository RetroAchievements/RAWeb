<?php

require_once __DIR__ . '/../../vendor/autoload.php';

runPublicApiMiddleware();

$data = getConsoleIDs();

echo json_encode($data);
