<?php
function log_email($logMessage)
{
    error_log($logMessage);

    // $fullmsg = $logMessage . "\n" . debug_string_backtrace();
    //if( !isAtHome() )
    //mail_utf8( "Scott@retroachievements.org", "RetroAchievements.org", "noreply@retroachievements.org", "Error Log", $fullmsg );
}

function log_sql($logMessage)
{
    error_log($logMessage);
}

function log_sql_fail()
{
    global $db;
    error_log(mysqli_error($db));
}
