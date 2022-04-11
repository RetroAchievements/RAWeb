<?php

/**
 * @throws Exception
 */
function log_sql_fail()
{
    global $db;
    $error = mysqli_error($db);
    if ($error) {
        if (filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)) {
            throw new Exception($error);
        }
        error_log($error);
    }
}
