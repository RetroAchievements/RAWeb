<?php
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
    // error_log("requestcreateuser.php failed 1 - $user $email $email2 ");
    echo "Username ($user) must consist only of letters or numbers. Please retry.<br>";
    return false;
}

if (mb_strlen($user) > 20) {
    // error_log("requestcreateuser.php failed 2 - $user $email $email2 ");
    echo "Username can be a maximum of 20 characters. Please retry.<br>";
    return false;
}

if (mb_strlen($user) < 2) {
    // error_log("requestcreateuser.php failed 3 - $user $email $email2 ");
    echo "Username must be at least 2 characters. Please retry.<br>";
    return false;
}

if (mb_strlen($pass) < 2) {
    // error_log("requestcreateuser.php failed 3.5 - $user $email $email2 ");
    echo "Password must be at least 2 characters. Please retry.<br>";
    return false;
}

if ($email !== $email2) {
    // error_log("requestcreateuser.php failed 4 - $user $email $email2 ");
    echo "Emails do not match... please retry.<br>";
    return false;
}

if (!checkEmail($email)) {
    // error_log("requestcreateuser.php failed 5 - $user $email $email2 ");
    echo "Email is not valid... please retry.<br>";
    return false;
}

if (getenv('GOOGLE_RECAPTCHA_SECRET')) {
    if (empty($_POST['g-recaptcha-response'])) {
        // nope
        return false;
    }

    //$resp = recaptcha_check_answer( getenv('GOOGLE_RECAPTCHA_SECRET'),
    //								$_SERVER["REMOTE_ADDR"],
    //								$_POST["recaptcha_challenge_field"],
    //								$_POST["recaptcha_response_field"]);
    //var_dump( $_POST );
    //	Send $_POST['g-recaptcha-response'] to https://www.google.com/recaptcha/api/siteverify
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = ['secret' => getenv('GOOGLE_RECAPTCHA_SECRET'), 'response' => $_POST['g-recaptcha-response']];

    // use key 'http' even if you send the request to https://...
    $options = ['http' => ['header' => "Content-type: application/x-www-form-urlencoded\r\n", 'method' => 'POST', 'content' => http_build_query($data),],];
    $context = stream_context_create($options);
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

$saltedPass = md5($pass . getenv('RA_PASSWORD_SALT'));

$query = "INSERT INTO UserAccounts (User, SaltedPass, EmailAddress, Permissions, RAPoints, fbUser, fbPrefs, cookie, appToken, appTokenExpiry, websitePrefs, LastLogin, LastActivityID, Motto, ContribCount, ContribYield, APIKey, APIUses, LastGameID, RichPresenceMsg, RichPresenceMsgDate, ManuallyVerified, UnreadMessageCount, TrueRAPoints, UserWallActive, PasswordResetToken, Untracked, email_backup) 
VALUES ( \"$user\", \"$saltedPass\", \"$email\", 0, 0, 0, 0, '', '', NULL, 63, null, 0, \"\", 0, 0, \"\", 0, 0, \"Unknown\", NULL, 0, 0, 0, 1, NULL, false, \"$email\")";
$dbResult = s_mysql_query($query);

if ($dbResult !== false) {
    // Instead of signing them in straight away...
    //generateCookie( $user, $cookie );
    // Create an email cookie and send them an email
    if (sendValidationEmail($user, $email) == false) {
        // error_log("Failed to send validation email to $user at $email");
    }

    if (copy(getenv('DOC_ROOT') . "public/UserPic/_User.png", getenv('DOC_ROOT') . "public/UserPic/$user.png") == false) {
        // error_log("Failed to create user pic for user $user");
    }

    header("Location: " . getenv('APP_URL') . "/?e=validateEmailPlease");

    echo "Created $user successfully!<br>";
} else {
    log_sql_fail();
    // error_log("requestcreateuser.php - Failed to create user $user");
    echo "Failed to create $user <br>";
}
