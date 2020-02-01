<?php

require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$data = getConsoleIDs();

echo json_encode($data);
