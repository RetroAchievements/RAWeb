<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

clearCookie('RA_Cookie');

header("Location: " . getenv('APP_URL'));
