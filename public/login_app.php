<?php

// Auto login from app uses token. Standard login from app uses password.
$user = requestInputPost('u');
$pass = requestInputPost('p');
$token = requestInputPost('t');

$response = authenticateFromPasswordOrAppToken($user, $pass, $token);

return response()->json($response, !$response['Success'] ? 401 : 200);
