<?php

require_once __DIR__ . '/../../../lib/bootstrap.php';

use RA\Permissions;

$ratingID = seekGET('i');
$ratingType = seekGET('t');
$ratingValue = seekGET('v');

settype($ratingID, 'integer');
settype($ratingType, 'integer');
settype($ratingValue, 'integer');

$validRating = ($ratingType == 1 || $ratingType == 3) && ($ratingValue >= 1 && $ratingValue <= 5);

if ($validRating
      && RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, Permissions::Registered)) {
    $success = submitGameRating($user, $ratingType, $ratingID, $ratingValue);
} else {
    $success = false;
}

echo json_encode([
    'Success' => $success,
]);
