<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("tfv")) {
    echo "FAILED";
    return;
}

$topicID = requestInputPost('t');
$field = requestInputPost('f');
$value = requestInputPost('v');

// error_log("requestModifyTopic, " . $field . ", " . "$value");

if (validateFromCookie($user, $unused, $permissions, \RA\Permissions::Registered)) {
    if (requestModifyTopic($user, $permissions, $topicID, $field, $value)) {
        if ($field == ModifyTopicField::DeleteTopic) {
            header("location: " . getenv('APP_URL') . "/forum.php?e=delete_ok");
        } else {
            header("location: " . getenv('APP_URL') . "/viewtopic.php?t=$topicID&e=modify_ok");
        }
        exit;
    } else {
        header("location: " . getenv('APP_URL') . "/viewtopic.php?t=$topicID&e=errors_in_modify");
        exit;
    }
}
