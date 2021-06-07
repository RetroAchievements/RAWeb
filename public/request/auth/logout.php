<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

RA_ClearCookie('RA_User');
RA_ClearCookie('RA_Cookie');

header("Location: " . getenv('APP_URL'));
