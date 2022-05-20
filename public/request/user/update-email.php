<?php

use RA\ArticleType;
use RA\Permissions;

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

if (!ValidatePOSTChars("ef")) {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=e_baddata");
    exit;
}

$email = requestInputPost('e');
$email2 = requestInputPost('f');

if ($email !== $email2) {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=e_notmatch");
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=e_badnewemail");
    exit;
}

if (authenticateFromCookie($user, $permissions, $userDetail)) {
    $dbResult = s_mysql_query(
        "UPDATE UserAccounts SET EmailAddress='$email', Permissions=" . Permissions::Unregistered . ", Updated=NOW() WHERE User='$user'"
    );

    if (!$dbResult) {
        header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=e_generalerror");
        exit;
    }

    sendValidationEmail($user, $email);

    addArticleComment('Server', ArticleType::UserModeration, $userDetail['ID'],
        $user . ' changed their email address'
    );

    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=e_changeok");
    exit;
}

header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=e_badcredentials");
