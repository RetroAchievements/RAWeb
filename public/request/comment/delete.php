<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

$articleID = seekPOSTorGET('a', 0, 'integer');
$commentID = seekPOSTorGET('c', 0, 'integer');

$response = [];
$response['ArtID'] = $articleID;
$response['CommentID'] = $commentID;
if (RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions, \RA\Permissions::Registered, $userID)) {
    $response['Success'] = RemoveComment($articleID, $commentID, $userID, $permissions);
} else {
    $response['Success'] = false;
}

echo json_encode($response);
