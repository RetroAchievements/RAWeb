<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$articleID = requestInput('a', 0, 'integer');
$commentID = requestInput('c', 0, 'integer');

$response = [];
$response['ArtID'] = $articleID;
$response['CommentID'] = $commentID;
if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Registered, $userID)) {
    $response['Success'] = RemoveComment($articleID, $commentID, $userID, $permissions);
} else {
    $response['Success'] = false;
}

echo json_encode($response);
