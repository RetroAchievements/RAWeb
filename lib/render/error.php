<?php
function RenderErrorCodeWarning($location, $errorCode)
{
    if (isset($errorCode)) {
        echo "<div class=$location>";
        echo "<h2>Information</h2>";

        if ($errorCode == "validatedEmail") {
            echo "<div id='warning'>Email validated!</div>";
        } elseif ($errorCode == "validateEmailPlease") {
            echo "<div id='warning'>An email has been sent to the email address you supplied. Please click the link in that email.</div>";
        } elseif ($errorCode == "incorrectpassword") {
            echo "<div id='warning'>Incorrect User/Password! Please re-enter.</div>";
        } elseif ($errorCode == "accountissue") {
            echo "<div id='warning'>There appears to be a problem with your account. Please contact the administrator <a href='" . getenv('APP_URL') . "/user/RAdmin'>here</a> for more details.</div>";
        } elseif ($errorCode == "notloggedin") {
            echo "<div id='warning'>Please log in.</div>";
        } elseif ($errorCode == "resetok") {
            echo "<div id='warning'>Reset was performed OK!</div>";
        } elseif ($errorCode == "resetfailed") {
            echo "<div id='warning'>Problems encountered while performing reset. Do you have any achievements to reset?</div>";
        } elseif ($errorCode == "modify_game_ok") {
            echo "<div id='warning'>Game modify successful!</div>";
        } elseif ($errorCode == "errors_in_modify_game") {
            echo "<div id='warning'>Problems encountered while performing modification. Does the target game already exist? If so, try a merge instead on the target game title.</div>";
        } elseif ($errorCode == "merge_success") {
            echo "<div id='warning'>Game merge successful!</div>";
        } elseif ($errorCode == "merge_failed") {
            echo "<div id='warning'>Problems encountered while performing merge. These errors have been reported and will be fixed soon... sorry!</div>";
        } elseif ($errorCode == "recalc_ok") {
            echo "<div id='warning'>Score recalculated! Your new score is shown at the top-right next to your avatar.</div>";
        } elseif ($errorCode == 'changeerror') {
            echo "<div id='warning'>Warning: An error was encountered. Please check and try again.</div>";
        } elseif ($errorCode == 'changeok') {
            echo "<div id='warning'>Info: Change(s) made successfully!</div>";
        } elseif ($errorCode == 'newspostsuccess') {
            echo "<div id='warning'>Info: News post added/updated successfully!</div>";
        } elseif ($errorCode == 'newspostfail') {
            echo "<div id='warning'>Warning! Post not made successfully. Do you have correct permissions?</div>";
        } elseif ($errorCode == 'uploadok') {
            echo "<div id='warning'>Info: Image upload OK!</div>";
        } elseif ($errorCode == 'modify_ok') {
            echo "<div id='warning'>Info: Modified OK!</div>";
        } elseif ($errorCode == 'sentok') {
            echo "<div id='warning'>Info: Message sent OK!</div>";
        } elseif ($errorCode == 'deleteok') {
            echo "<div id='warning'>Info: Message deleted OK!</div>";
        } elseif ($errorCode == 'success') {
            echo "<div id='warning'>Info: Successful!</div>";
        } elseif ($errorCode == 'delete_ok') {
            echo "<div id='warning'>Info: Deleted OK!</div>";
        } elseif ($errorCode == 'badcredentials') {
            echo "<div id='warning'>There appears to be a problem with your account. Please contact <a href='" . getenv('APP_URL') . "/user/RAdmin'>RAdmin</a> for more details.</div>";
        } elseif ($errorCode == 'friendadded') {
            echo "<div id='warning'>Friend Added!</div>";
        } elseif ($errorCode == 'friendconfirmed') {
            echo "<div id='warning'>Friend Confirmed!</div>";
        } elseif ($errorCode == 'friendrequested') {
            echo "<div id='warning'>Friend Request sent!</div>";
        } elseif ($errorCode == 'friendremoved') {
            echo "<div id='warning'>Friend Removed.</div>";
        } elseif ($errorCode == 'friendblocked') {
            echo "<div id='warning'>User blocked.</div>";
        } elseif ($errorCode == 'userunblocked') {
            echo "<div id='warning'>User unblocked.</div>";
        } elseif ($errorCode == 'newadded') {
            echo "<div id='warning'>Friend request sent.</div>";
        } elseif ($errorCode == 'OK' || $errorCode == 'ok') {
            echo "<div id='warning'>Performed OK!</div>";
        } elseif ($errorCode == 'badpermissions') {
            echo "<div id='warning'>You don't have permission to view this page! If this is incorrect, please leave a message in the forums.</div>";
        } elseif ($errorCode == 'nopermission') {
            echo "<div id='warning'>You don't have permission to view this page! If this is incorrect, please leave a message in the forums.</div>";
        } elseif ($errorCode == 'checkyouremail') {
            echo "<div id='warning'>Please check your email for further instructions.</div>";
        } elseif ($errorCode == 'newpasswordset') {
            echo "<div id='warning'>New password accepted. Please log in.</div>";
        } elseif ($errorCode == 'issue_submitted') {
            echo "<div id='warning'>Your issue ticket has been successfully submitted.</div>";
        } elseif ($errorCode == 'issue_failed') {
            echo "<div id='warning'>Sorry. There was an issue submitting your ticket.</div>";
        } elseif ($errorCode == 'subscription_update_fail') {
            echo "<div id='warning'>Failed to update topic subscription.</div>";
        }

        echo "</div>";
    }
}
