<?php

function debug_string_backtrace()
{
    ob_start();
    debug_print_backtrace();
    $trace = ob_get_contents();
    ob_end_clean();

    // Remove first item from backtrace as it's this function which
    // is redundant.
    $trace = preg_replace('/^#0\s+' . __FUNCTION__ . "[^\n]*\n/", '', $trace, 1);

    //  PHP warning?
    // Renumber backtrace items.
    //$trace = preg_replace( '/^#(\d+)/me', '\'#\' . ($1 - 1)', $trace );

    return $trace;
}

function ProfileStamp($message = null, $echo = false)
{
    global $_profileTimer;
    global $_loadDuration;
    if ($_loadDuration != 0) {
        $newTime = microtime(true);
        $_loadDuration = $newTime - $_profileTimer;
        $_profileTimer = $newTime;
        // error_log("PROFILE - " . CurrentPageURL() . " - took " . sprintf('%1.4f', ($_loadDuration)) . "s...");
        if ($echo) {
            echo "PROFILE - " . CurrentPageURL() . " - took " . sprintf('%1.4f', ($_loadDuration)) . "s...";
        }

        if (isset($message) && mb_strlen($message) > 0) {
            // error_log(" - " . $message);
        }
        //return " <span style='font-size:x-small;'>(Generated in " . sprintf( '%1.4f', ($_loadDuration) ) . "s)</span>";
    } else {
        $_loadDuration = microtime(true) - $_profileTimer;
    }
}
