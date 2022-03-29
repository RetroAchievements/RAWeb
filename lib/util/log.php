<?php

function log_sql_fail()
{
    global $db;
    $error = mysqli_error($db);
    // if(getenv('APP_DEBUG')) {
    //     throw new Exception($error);
    // }
    if ($error) {
        error_log($error);
    }
}
