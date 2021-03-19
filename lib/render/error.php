<?php
/**
 * @param string $errorCode
 * @return string
 */
function RenderErrorCodeWarning($errorCode)
{
    if (empty($errorCode)) {
        return '';
    }
    $errorMessages = [
        'accountissue' => "There appears to be a problem with your account. Please contact the administrator <a href='" . getenv('APP_URL') . "/user/RAdmin'>here</a> for more details.",
        'badcredentials' => "There appears to be a problem with your account. Please contact <a href='" . getenv('APP_URL') . "/user/RAdmin'>RAdmin</a> for more details.",
        'badpermissions' => "You don't have permission to view this page! If this is incorrect, please leave a message in the forums.",
        'changeerror' => "Warning: An error was encountered. Please check and try again.",
        'changeok' => "Info: Change(s) made successfully!",
        'checkyouremail' => "Please check your email for further instructions.",
        'delete_ok' => "Info: Deleted OK!",
        'deleteok' => "Info: Message deleted OK!",
        'error' => "An error occurred!",
        'errors_in_modify_game' => "Problems encountered while performing modification. Does the target game already exist? If so, try a merge instead on the target game title.",
        'friendadded' => "Friend Added!",
        'friendblocked' => "User blocked.",
        'friendconfirmed' => "Friend Confirmed!",
        'friendremoved' => "Friend Removed.",
        'friendrequested' => "Friend Request sent!",
        'incorrectpassword' => "Incorrect User/Password! Please re-enter.",
        'issue_failed' => "Sorry. There was an issue submitting your ticket.",
        'issue_submitted' => "Your issue ticket has been successfully submitted.",
        'merge_failed' => "Problems encountered while performing merge. These errors have been reported and will be fixed soon... sorry!",
        'merge_success' => "Game merge successful!",
        'modify_game_ok' => "Game modify successful!",
        'modify_ok' => "Info: Modified OK!",
        'newadded' => "Friend request sent.",
        'newpasswordset' => "New password accepted. Please log in.",
        'newspostfail' => "Warning! Post not made successfully. Do you have correct permissions?",
        'newspostsuccess' => "Info: News post added/updated successfully!",
        'nopermission' => "You don't have permission to view this page! If this is incorrect, please leave a message in the forums.",
        'notloggedin' => "Please log in.",
        'ok' => "Performed OK!",
        'recalc_ok' => "Score recalculated! Your new score is shown at the top-right next to your avatar.",
        'resetfailed' => "Problems encountered while performing reset. Do you have any achievements to reset?",
        'resetok' => "Reset was performed OK!",
        'sentok' => "Info: Message sent OK!",
        'subscription_update_fail' => "Failed to update topic subscription.",
        'success' => "Info: Successful!",
        'uploadok' => "Info: Image upload OK!",
        'userunblocked' => "User unblocked.",
        'validatedemail' => "Email validated!",
        'validateemailplease' => "An email has been sent to the email address you supplied. Please click the link in that email.",
    ];
    $message = $errorMessages[mb_strtolower($errorCode)] ?? null;
    if (!$message) {
        return '';
    }
    echo "<div id='warning'>$message</div>";
}
