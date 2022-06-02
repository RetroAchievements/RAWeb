<?php

use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("aptlg")) {
    echo "FAILED";
    exit;
}

$author = requestInputPost('a');
$payload = requestInputPost('p');
$title = requestInputPost('t');
$link = requestInputPost('l');
$image = requestInputPost('g');
$id = requestInputPost('i', null, 'integer');

$payload = str_replace("_http_", "http", $payload);
$title = str_replace("_http_", "http", $title);
$link = str_replace("_http_", "http", $link);
$image = str_replace("_http_", "http", $image);

if (!authenticateFromCookie($user, $permissions, $userDetails, Permissions::Developer)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

requestModifyNews($author, $id, $title, $payload, $link, $image);
echo json_encode(['Success' => true]);
