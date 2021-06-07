<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../lib/bootstrap.php';

runPublicApiMiddleware();

$dataOut = [];
$numFound = getTopUsersByScore(10, $dataOut, null);

echo json_encode($dataOut);
