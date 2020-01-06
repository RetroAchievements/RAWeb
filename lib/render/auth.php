<?php
function RenderLoginComponent($user, $points, $errorCode, $inline = false)
{
    if ($inline == true) {
        echo "<div class='both'><div class=''>";
    } else {
        echo "<div class=''><div class=''>";
    }

    if ($user == false) {
        echo "<h3>login</h3>";
        echo "<div class='infobox'>";
        echo "<b>login</b> to " . getenv('APP_NAME') . ":<br>";

        echo "<form method='post' action='/request/auth/login.php'>";
        echo "<div>";
        echo "<input type='hidden' name='r' value='" . $_SERVER['REQUEST_URI'] . "' />";
        echo "<table style='logintable'><tbody>";
        echo "<tr>";
        echo "<td style='loginfieldscell'>User:&nbsp;</td>";
        echo "<td style='loginfieldscell'><input type='text' name='u' size='16' class='loginbox' value='' /></td>";
        echo "<td></td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td style='loginfieldscell'>Pass:&nbsp;</td>";
        echo "<td style='loginfieldscell'><input type='password' name='p' size='16' class='loginbox' value='' /></td>";
        echo "<td style='loginbuttoncell'><input type='submit' value='Login' name='submit' class='loginbox' /></td>";
        echo "</tr>";
        echo "</tbody></table>";
        echo "</div>";
        echo "</form>";

        echo "or <a href='/createaccount.php'>create a new account</a><br>";

        echo "</div>";
    } else {
        echo "<h3>$user</h3>";
        echo "<div class='infobox'>";

        echo "<p>";
        echo "<img class='userpic' src='/UserPic/$user.png' alt='$user' style='float:right' align='right' width='128' height='128' />";

        if ($errorCode == "validatedEmail") {
            echo "Welcome, <a href='/user/$user'>$user</a>!<br>";
        } else {
            echo "<strong><a href='/user/$user'>$user</a></strong> ($points)<br>";
        }

        echo "<a href='/request/auth/logout.php?Redir=" . $_SERVER['REQUEST_URI'] . "'>logout</a><br>";

        echo "</p>";

        echo "</div>";
    }
    echo "<br>";
    echo "</div>";
    echo "</div>";
}
