<?php
require_once __DIR__ . '/../../lib/bootstrap.php';

$user = seekGET('u');
$ratingID = seekGET('i');
$ratingType = seekGET('t');
$ratingValue = seekGET('v');

settype($ratingID, 'integer');
settype($ratingType, 'integer');
settype($ratingValue, 'integer');

$success = submitGameRating($user, $ratingType, $ratingID, $ratingValue);

echo json_encode([
    'Success' => $success,
]);
