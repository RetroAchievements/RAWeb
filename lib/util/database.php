<?php
function SQL_ASSERT($dbResult)
{
    if ($dbResult == false) {
        global $db;
        error_log("query failed:" . mysqli_error($db));
        log_sql_fail();
    }
}

function sanitiseSQL($query)
{
    if (mb_strrchr($query, ';') !== false) {
        error_log(__FUNCTION__ . " failed(;): query:$query");
        return false;
    } else {
        if (mb_strrchr($query, '/') !== false) {
            error_log(__FUNCTION__ . " failed(/): query:$query");
            return false;
        } else {
            if (mb_strrchr($query, '\\') !== false) {
                error_log(__FUNCTION__ . " failed(\\): query:$query");
                return false;
            } else {
                if (mb_strstr($query, "--") !== false) {
                    error_log(__FUNCTION__ . " failed(--): query:$query");
                    return false;
                } else {
                    return true;
                }
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

        if (DUMP_SQL) {
            echo "$query<br><br>";
        }

        if (PROFILE_SQL) {
            ProfileStamp($query);
        }

        return mysqli_query($db, $query);
    } else {
        return false;
    }
}
