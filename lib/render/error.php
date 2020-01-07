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
        'validatedEmail' => "Email validated!",
        'validateEmailPlease' => "An email has been sent to the email address you supplied. Please click the link in that email.",
        'incorrectpassword' => "Incorrect User/Password! Please re-enter.",
        'accountissue' => "There appears to be a problem with your account. Please contact the administrator <a href='" . getenv('APP_URL') . "/user/RAdmin'>here</a> for more details.",
        'notloggedin' => "Please log in.",
        'resetok' => "Reset was performed OK!",
        'resetfailed' => "Problems encountered while performing reset. Do you have any achievements to reset?",
        'modify_game_ok' => "Game modify successful!",
        'errors_in_modify_game' => "Problems encountered while performing modification. Does the target game already exist? If so, try a merge instead on the target game title.",
        'merge_success' => "Game merge successful!",
        'merge_failed' => "Problems encountered while performing merge. These errors have been reported and will be fixed soon... sorry!",
        'recalc_ok' => "Score recalculated! Your new score is shown at the top-right next to your avatar.",
        'changeerror' => "Warning: An error was encountered. Please check and try again.",
        'changeok' => "Info: Change(s) made successfully!",
        'newspostsuccess' => "Info: News post added/updated successfully!",
        'newspostfail' => "Warning! Post not made successfully. Do you have correct permissions?",
        'uploadok' => "Info: Image upload OK!",
        'modify_ok' => "Info: Modified OK!",
        'sentok' => "Info: Message sent OK!",
        'deleteok' => "Info: Message deleted OK!",
        'success' => "Info: Successful!",
        'delete_ok' => "Info: Deleted OK!",
        'badcredentials' => "There appears to be a problem with your account. Please contact <a href='" . getenv('APP_URL') . "/user/RAdmin'>RAdmin</a> for more details.",
        'friendadded' => "Friend Added!",
        'friendconfirmed' => "Friend Confirmed!",
        'friendrequested' => "Friend Request sent!",
        'friendremoved' => "Friend Removed.",
        'friendblocked' => "User blocked.",
        'userunblocked' => "User unblocked.",
        'newadded' => "Friend request sent.",
        'ok' => "Performed OK!",
        'badpermissions' => "You don't have permission to view this page! If this is incorrect, please leave a message in the forums.",
        'nopermission' => "You don't have permission to view this page! If this is incorrect, please leave a message in the forums.",
        'checkyouremail' => "Please check your email for further instructions.",
        'newpasswordset' => "New password accepted. Please log in.",
        'issue_submitted' => "Your issue ticket has been successfully submitted.",
        'issue_failed' => "Sorry. There was an issue submitting your ticket.",
        'subscription_update_fail' => "Failed to update topic subscription.",
    ];
    $message = $errorMessages[strtolower($errorCode)] ?? null;
    if (!$message) {
        return '';
    }
    echo "<div id='warning'>$message</div>";
}
