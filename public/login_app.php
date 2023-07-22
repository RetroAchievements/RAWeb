<?php

// Auto login from app uses token. Standard login from app uses password.
$user = request()->post('u', '');
$pass = request()->post('p');
$token = request()->post('t');

$response = authenticateForConnect($user, $pass, $token);

return response()->json($response, !$response['Success'] ? 401 : 200);
