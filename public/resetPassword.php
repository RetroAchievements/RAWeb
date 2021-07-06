<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/bootstrap.php';

$allowNewPasswordEntry = false;

$user = requestInputSanitized('u');
$passResetToken = requestInputSanitized('t');
if (isset($passResetToken) && isset($user)) {
    if (isValidPasswordResetToken($user, $passResetToken)) {
        $allowNewPasswordEntry = true;
    }
}

$errorCode = requestInputSanitized('e');
RenderHtmlStart();
RenderHtmlHead("Password Reset");
?>
<body>
<?php RenderTitleBar(null, 0, 0, 0, $errorCode); ?>
<?php RenderToolbar(null, 0); ?>

<div id="mainpage">
    <div id="fullcontainer">
        <?php
        echo "<h2 class='longheader'>Password Reset</h2>";

        if ($allowNewPasswordEntry == null) {
            //	Request username for password reset:
            echo "<h4 class='longheader'>Enter username for password reset:</h2>";

            echo "<div class='longer'>";
            echo "<form action='/request/auth/send-password-reset-email.php' method='post'>";
            echo "<input type='text' name='u' value='' />";
            echo "&nbsp;&nbsp;";
            echo "<input type='submit' value='Request Reset' />";
            echo "</form>";
            echo "</div>";
        } else {
            //	Enter new password for this user:
            echo "<h4 class='longheader'>Enter new Password for $user:</h4>";

            echo "<div class='longer'>";
            echo "<form action='/request/auth/update-password.php' method='post'>";
            echo "<input type='password' name='x' size='42' />&nbsp;";
            echo "<input type='password' name='y' size='42' />&nbsp;";
            echo "<input type='hidden' name='t' value='$passResetToken' />";
            echo "<input type='hidden' name='u' value='$user' />";
            echo "&nbsp;&nbsp;";
            echo "<input type='submit' value='Change Password' />";
            echo "</form>";
            echo "</div>";
        }
        ?>
        <br>
    </div>
</div>
<?php RenderFooter(); ?>
</body>
<?php RenderHtmlEnd(); ?>
