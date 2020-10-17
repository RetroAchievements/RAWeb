<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

if (!ValidatePOSTChars("efcu")) {
    echo "FAILED";
    // error_log(__FILE__);
    // error_log("Cannot validate efcu input...");
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=e_baddata");
}

$email = requestInputPost('e');
$email2 = requestInputPost('f');
$user = requestInputPost('u');
$cookie = requestInputPost('c');

if ($email !== $email2) {
    header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=e_notmatch");
} else {
    if (filter_var($email, FILTER_VALIDATE_EMAIL) == false) {
        header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=e_badnewemail");
    } else {
        if (validateUser_cookie($user, $cookie, 0) == true) {
            $query = "UPDATE UserAccounts SET EmailAddress='$email', Updated=NOW() WHERE User='$user'";
            $dbResult = s_mysql_query($query);
            if ($dbResult) {
                // error_log(__FILE__);
                // error_log("$user changed email to $email");

                header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=e_changeok");
            } else {
                // error_log(__FILE__);
                // error_log("$email,$email2,$user,$cookie");
                header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=e_generalerror");
            }
        } else {
            header("Location: " . getenv('APP_URL') . "/controlpanel.php?e=e_badcredentials");
        }
    }
}
