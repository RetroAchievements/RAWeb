<?php

use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$articleID = requestInput('a', 0, 'integer');
$commentID = requestInput('c', 0, 'integer');

$response = [];
$response['ArtID'] = $articleID;
$response['CommentID'] = $commentID;
if (authenticateFromCookie($user, $permissions, $userDetails, Permissions::Registered)) {
    $response['Success'] = RemoveComment($articleID, $commentID, $userDetails['ID'], $permissions);
} else {
    $response['Success'] = false;
}

echo json_encode($response, JSON_THROW_ON_ERROR);
