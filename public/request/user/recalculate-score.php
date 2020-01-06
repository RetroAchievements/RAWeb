<?php
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("u")) {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=invalidparams");
    exit;
}

$user = seekPOST('u');

if (recalcScore($user)) {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=recalc_ok");
    exit;
} else {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=recalc_error");
    exit;
}
