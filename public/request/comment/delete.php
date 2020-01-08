<?php
require_once __DIR__ . '/../../../lib/bootstrap.php';

$articleID = seekPOSTorGET('a', 0, 'integer');
$commentID = seekPOSTorGET('c', 0, 'integer');

$response = [];
$response['ArtID'] = $articleID;
$response['CommentID'] = $commentID;
if(RA_ReadCookieCredentials($user, $points, $truePoints, $unreadMessageCount, $permissions,\RA\Permissions::Registered)) {
    $response['Success'] = RemoveComment($articleID, $commentID);
} else {
    $response['Success'] = false;
}

echo json_encode($response);
