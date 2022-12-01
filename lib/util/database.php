<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

function getMysqliConnection(): mysqli
{
    return app('mysqli');
}

function sanitize_sql_inputs(&...$inputs): void
{
    $db = getMysqliConnection();

    foreach ($inputs as &$input) {
        if (!empty($input)) {
            $input = mysqli_real_escape_string($db, $input);
        }
    }
}

function sanitiseSQL($query): bool
{
    if (mb_strrchr($query, ';') !== false) {
        return false;
    } else {
        if (mb_strrchr($query, '\\') !== false) {
            return false;
        } else {
            if (mb_strstr($query, "--") !== false) {
                return false;
            } else {
                return true;
            }
        }
    }
}

function s_mysql_query($query): mysqli_result|bool
{
    $db = getMysqliConnection();

    if (sanitiseSQL($query)) {
        return s_mysql_sanitized_query($query);
    } else {
        return false;
    }
}

function s_mysql_sanitized_query($query): mysqli_result|bool
{
    $db = getMysqliConnection();

    $start = microtime(true);

    $result = mysqli_query($db, $query);

    $elapsed = round((microtime(true) - $start) * 1000, 2);

    DB::connection()->logQuery($query, [], $elapsed);

    return $result;
}

/**
 * @throws Exception
 */
function log_sql_fail(): void
{
    $db = getMysqliConnection();
    $error = mysqli_error($db);
    if ($error) {
        if (config('app.debug')) {
            throw new Exception($error);
        }
        Log::error($error);
    }
}
