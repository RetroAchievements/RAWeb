<?php
function log_email($logMessage)
{
    $fullmsg = $logMessage . "\n" . debug_string_backtrace();
    error_log($fullmsg);

    //if( !isAtHome() )
    //mail_utf8( "Scott@retroachievements.org", "RetroAchievements.org", "noreply@retroachievements.org", "Error Log", $fullmsg );
}

function log_sql($logMessage)
{
    if (isAtHome()) {
        error_log($logMessage . "\n", 3, "storage/logs/queries.log");
    } else {
        error_log($logMessage . "\n", 3, getenv('DOC_ROOT') . "storage/logs/queries.log");
    }
}

function log_sql_fail()
{
    global $db;

    error_log(mysqli_errno($db) . ": " . mysqli_error($db), 3, getenv('DOC_ROOT') . "storage/logs/queries.log");
    error_log("SQL failed: " . mysqli_error($db));
    log_email("SQL failed: " . mysqli_error($db));
}

function var_dump_errorlog($var)
{
    ob_start();
    var_dump($var);
    $contents = ob_get_contents();
    ob_end_clean();
    error_log("ErrorLog Dump: " . $contents);
}
