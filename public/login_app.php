<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

// Auto login from app uses token. Standard login from app uses password.
$user = requestInputPost('u');
$pass = requestInputPost('p');
$token = requestInputPost('t');

$response = authenticateFromPasswordOrAppToken($user, $pass, $token);
if (!$response['Success']) {
    http_response_code(401);
}

echo json_encode($response, JSON_THROW_ON_ERROR);
