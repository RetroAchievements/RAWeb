<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("u")) {
    header("Location: " . getenv('APP_URL') . "/index.php?e=baddata");
    exit;
}

$user = requestInputPost('u');
RequestPasswordReset($user);
header("Location: " . getenv('APP_URL') . "/index.php?e=checkyouremail");
