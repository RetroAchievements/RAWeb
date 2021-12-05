<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../lib/bootstrap.php';

function checkEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

$user = $_POST["u"];
$pass = $_POST["p"];
$email = $_POST["e"];
$email2 = $_POST["f"];

if (ctype_alnum($user) == false) {
    echo "Username ($user) must consist only of letters or numbers. Please retry.<br>";
    return false;
}

if (mb_strlen($user) > 20) {
    echo "Username can be a maximum of 20 characters. Please retry.<br>";
    return false;
}

if (mb_strlen($user) < 4) {
    echo "Username must be at least 4 characters. Please retry.<br>";
    return false;
}

if (mb_strlen($pass) < 8) {
    echo "Password must be at least 8 characters. Please retry.<br>";
    return false;
}

if ($pass == $user) {
    echo "Password and username must not be identical. Please retry.<br>";
    return false;
}

if ($email !== $email2) {
    echo "Emails do not match... please retry.<br>";
    return false;
}

if (!checkEmail($email)) {
    echo "Email is not valid... please retry.<br>";
    return false;
}

if (getenv('GOOGLE_RECAPTCHA_SECRET')) {
    if (empty($_POST['g-recaptcha-response'])) {
        // nope
        return false;
    }

    //$resp = recaptcha_check_answer( getenv('GOOGLE_RECAPTCHA_SECRET'), $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
    // Send $_POST['g-recaptcha-response'] to https://www.google.com/recaptcha/api/siteverify
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = ['secret' => getenv('GOOGLE_RECAPTCHA_SECRET'), 'response' => $_POST['g-recaptcha-response']];

    // use key 'http' even if you send the request to https://...
    $context = stream_context_create([
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
        ],
    ]);
    $result = file_get_contents($url, false, $context);
    $resultJSON = json_decode($result, true);

    if (array_key_exists('success', $resultJSON) && $resultJSON['success'] != true) {
        // error_log("requestcreateuser.php failed 6 - $user $email $email2 ");
        echo "Captcha field failed!... please retry.<br>";
        return false;
    }
}

$query = "SELECT User FROM UserAccounts WHERE User='$user'";
$dbResult = s_mysql_query($query);

if ($dbResult !== false && mysqli_num_rows($dbResult) == 1) {
    // error_log("requestcreateuser.php failed 6 - $user $email $email2 ");
    echo "That username is already taken...<br>";

    return false;
}

$hashedPassword = hashPassword($pass);

$query = "INSERT INTO UserAccounts (User, Password, SaltedPass, EmailAddress, Permissions, RAPoints, fbUser, fbPrefs, cookie, appToken, appTokenExpiry, websitePrefs, LastLogin, LastActivityID, Motto, ContribCount, ContribYield, APIKey, APIUses, LastGameID, RichPresenceMsg, RichPresenceMsgDate, ManuallyVerified, UnreadMessageCount, TrueRAPoints, UserWallActive, PasswordResetToken, Untracked, email_backup) 
VALUES ( '$user', '$hashedPassword', '', '$email', 0, 0, 0, 0, '', '', NULL, 63, null, 0, '', 0, 0, '', 0, 0, '', NULL, 0, 0, 0, 1, NULL, false, '$email')";
$dbResult = s_mysql_query($query);

if ($dbResult !== false) {
    // Instead of signing them in straight away...
    //generateCookie( $user, $cookie );
    // Create an email cookie and send them an email
    if (sendValidationEmail($user, $email) == false) {
        // error_log("Failed to send validation email to $user at $email");
    }

    /**
     * do not copy avatar to reduce data waste
     * static media host should be configured to serve the default avatar for any missing files instead
     * disabled by default for local development
     */
    if (!getenv('RA_AVATAR_FALLBACK')) {
        copy(getenv('DOC_ROOT') . "public/UserPic/_User.png", getenv('DOC_ROOT') . "public/UserPic/$user.png");
    }

    header("Location: " . getenv('APP_URL') . "/?e=validateEmailPlease");

    echo "Created $user successfully!<br>";
} else {
    log_sql_fail();
    // error_log("requestcreateuser.php - Failed to create user $user");
    echo "Failed to create $user <br>";
}
