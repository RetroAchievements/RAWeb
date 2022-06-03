<?php

use RA\ActivityType;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

$user = requestInputPost('u');
$pass = requestInputPost('p');
$redirect = requestInputPost('r', '/');
$redirect = str_replace(['&e=', '?e=', 'incorrectpassword', 'notloggedin'], '', $redirect);

if (!authenticateFromPassword($user, $pass)) {
    redirect($redirect . (parse_url($redirect, PHP_URL_QUERY) ? '&' : '?') . 'e=incorrectpassword');
    exit;
}

generateCookie($user);

postActivity($user, ActivityType::Login, null);

redirect(url($redirect));
