<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

if (!ValidatePOSTChars("u")) {
    // error_log(__FILE__);
    // error_log("Cannot validate u input...");
    header("Location: " . getenv('APP_URL') . "/index.php?e=baddata");
}

// error_log(__FILE__);

$user = requestInputPost('u');
RequestPasswordReset($user);
header("Location: " . getenv('APP_URL') . "/index.php?e=checkyouremail");
