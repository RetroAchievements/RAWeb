<?php

use Symfony\Component\HttpFoundation\Response;

$requestType = request()->input('r');
$user = request()->input('u');
$token = request()->input('t');

if (!$token) {
    return response()->json([
        'Success' => false,
        'Error' => "Missing Token",
    ], Response::HTTP_UNAUTHORIZED);
}

if (!authenticateFromAppToken($user, $token, $permissions)) {
    return response()
        ->json([
            'Success' => false,
            'Error' => "Unknown Request: '$requestType'",
        ], Response::HTTP_UNAUTHORIZED);
}

// Infer request type from app
// TODO: remove if not required anymore
if (isset($_FILES['file']) && isset($_FILES['file']['name'])) {
    $requestType = mb_substr($_FILES['file']['name'], 0, -4);
}

if ($requestType !== 'uploadbadgeimage') {
    return response()->json([
        'Success' => false,
        'Error' => "Unknown Request: '$requestType'",
    ]);
}

try {
    $badgeIterator = UploadBadgeImage($_FILES['file']);
} catch (Exception $exception) {
    return response()->json([
        'Success' => false,
        'Error' => $exception->getMessage(),
    ]);
}

return response()->json([
    'Success' => true,
    'Response' => [
        // RALibretro uses BadgeIter to associate the uploaded badge to the achievement
        'BadgeIter' => $badgeIterator,
    ],
]);
