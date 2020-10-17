<?php

function sanitize_sql_inputs(&...$inputs)
{
    global $db;
    foreach ($inputs as &$input) {
        if (!empty($input)) {
            $input = mysqli_real_escape_string($db, $input);
        }
    }
}

function SQL_ASSERT($dbResult)
{
    if ($dbResult == false) {
        log_sql_fail();
    }
}

function sanitiseSQL($query)
{
    if (mb_strrchr($query, ';') !== false) {
        // error_log(__FUNCTION__ . " failed(;): query:$query");
        return false;
    } else {
        if (mb_strrchr($query, '\\') !== false) {
            // error_log(__FUNCTION__ . " failed(\\): query:$query");
            return false;
        } else {
            if (mb_strstr($query, "--") !== false) {
                // error_log(__FUNCTION__ . " failed(--): query:$query");
                return false;
            } else {
                return true;
            }
        }
    }
}

/**
 * @param $query
 * @return bool|mysqli_result
 */
function s_mysql_query($query)
{
    global $db;
    if (sanitiseSQL($query)) {
        global $g_numQueries;
        $g_numQueries++;
        return mysqli_query($db, $query);
    } else {
        return false;
    }
}
