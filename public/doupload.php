<?php

use RA\FilenameIterator;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

$requestType = requestInput('r');
$user = requestInput('u');
$token = requestInput('t');

$bounceReferrer = requestInput('b'); // TBD: Remove!

if (!authenticateFromAppToken($user, $token, $permissions)) {
    http_response_code(401);
    echo json_encode(['Success' => false]);
    exit;
}

// Infer request type from app
// TODO: remove if not required anymore
if (isset($_FILES['file']) && isset($_FILES['file']['name'])) {
    $requestType = mb_substr($_FILES['file']['name'], 0, -4);
}

if ($requestType !== 'uploadbadgeimage') {
    echo json_encode([
        'Success' => false,
        'Error' => "Unknown Request: '$requestType'",
    ]);
    exit;
}

try {
    UploadBadgeImage($_FILES['file']);
} catch (Exception $exception) {
    echo json_encode([
        'Success' => false,
        'Error' => $exception->getMessage(),
    ]);
    exit;
}

echo json_encode([
    'Success' => true,
    'Response' => [
        // TODO does RALibretro need the iterator?
        'BadgeIter' => FilenameIterator::getBadgeIterator(),
        'Filename' => $_FILES['file']['name'],
        'Size' => $_FILES['file']['size'],
    ],
]);
