<?php

$user = request()->query('u');
$token = request()->query('t');
$allowNewPasswordEntry = is_string($user) && is_string($token) && isValidPasswordResetToken($user, $token);

RenderContentStart("Password Reset");
?>
<article>
    <?php
    echo "<h2>Password Reset</h2>";
    if (!$allowNewPasswordEntry) {
        // Request username for password reset:
        echo "<h4>Enter username for password reset:</h2>";

        echo "<div class='longer'>";
        echo "<form action='/request/auth/send-password-reset-email.php' method='post'>";
        echo csrf_field();
        echo "<input type='text' name='username'>";
        echo "&nbsp;&nbsp;";
        echo "<button class='btn'>Request Reset</button>";
        echo "</form>";
        echo "</div>";
    } else {
        // Enter new password for this user:
        echo "<h4>Enter new Password for $user:</h4>";

        echo "<div class='longer'>";
        echo "<form action='/request/auth/reset-password.php' method='post'>";
        echo csrf_field();
        echo "<input type='password' name='password'>&nbsp;";
        echo "<input type='password' name='password_confirmation'>&nbsp;";
        echo "<input type='hidden' name='token' value='$token'>";
        echo "<input type='hidden' name='username' value='$user'>";
        echo "&nbsp;&nbsp;";
        echo "<button class='btn'>Change Password</button>";
        echo "</form>";
        echo "</div>";
    }
    ?>
</article>
<?php RenderContentEnd(); ?>
