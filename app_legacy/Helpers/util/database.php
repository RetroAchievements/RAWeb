<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * legacyDbAll() behaves like mysqli_fetch_all().
 * PDO will return an array of objects by default which will be cast to arrays.
 */
function legacyDbFetchAll(string $query, array $bindings = []): Collection
{
    return collect(legacyDbSelect($query, $bindings))
        ->map(fn ($row) => (array) $row);
}

/**
 * legacyDbFirst() behaves like a single call to mysqli_fetch_assoc().
 * Note that it does not work in a while loop like mysqli_fetch_assoc().
 * Use legacyDbAll() to fetch all rows.
 */
function legacyDbFetch(string $query, array $bindings = []): ?array
{
    $result = legacyDbSelect($query, $bindings);

    return ($result[0] ?? null) ? (array) $result[0] : null;
}

function legacyDbSelect(string $query, array $bindings = []): array
{
    return DB::connection('mysql_legacy')
        ->select(DB::connection('mysql_legacy')->raw($query), $bindings);
}

function legacyDbStatement(string $query, array $bindings = []): bool
{
    return DB::connection('mysql_legacy')
        ->statement(DB::connection('mysql_legacy')->raw($query), $bindings);
}

function legacyDbDriver(): string
{
    return DB::connection('mysql_legacy')->getDriverName();
}

function diffMinutesRemainingStatement(string $column, string $alias): string
{
    return match (legacyDbDriver()) {
        'sqlite' => "ROUND((JULIANDAY($column) - JULIANDAY(CURRENT_TIMESTAMP)) * 1440) AS $alias",
        // mysql
        default => "TIMESTAMPDIFF(MINUTE, NOW(), $column) AS $alias",
    };
}

function diffMinutesPassedStatement(string $column, string $alias): string
{
    return match (legacyDbDriver()) {
        'sqlite' => "ROUND((JULIANDAY(CURRENT_TIMESTAMP) - JULIANDAY($column)) * 1440) AS $alias",
        // mysql
        default => "TIMESTAMPDIFF(MINUTE, $column, NOW()) AS $alias",
    };
}

/**
 * @deprecated
 */
function getMysqliConnection(): mysqli
{
    return app('mysqli');
}

/**
 * @deprecated
 */
function sanitize_sql_inputs(&...$inputs): void
{
    $db = getMysqliConnection();

    foreach ($inputs as &$input) {
        if (!empty($input)) {
            $input = mysqli_real_escape_string($db, $input);
        }
    }
}

/**
 * @deprecated
 */
function s_mysql_query($query): mysqli_result|bool
{
    $db = getMysqliConnection();

    $start = microtime(true);

    $result = mysqli_query($db, $query);

    $elapsed = round((microtime(true) - $start) * 1000, 2);

    DB::connection('mysql_legacy')->logQuery($query, [], $elapsed);

    return $result;
}

/**
 * @deprecated
 */
function log_sql_fail(): void
{
    $db = getMysqliConnection();
    $error = mysqli_error($db);
    if ($error) {
        throw new Exception($error);
    }
}
