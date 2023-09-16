<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * legacyDbFetchAll() behaves like mysqli_fetch_all().
 * PDO will return an array of objects by default which will be cast to arrays.
 *
 * @return Collection<int, array>
 */
function legacyDbFetchAll(string $query, array $bindings = []): Collection
{
    return collect(legacyDbSelect($query, $bindings))
        ->map(fn ($row) => (array) $row);
}

/**
 * legacyDbFetch() behaves like a single call to mysqli_fetch_assoc().
 * Note that it does not work in a while loop like mysqli_fetch_assoc().
 * Use legacyDbFetchAll() to fetch all rows.
 */
function legacyDbFetch(string $query, array $bindings = []): ?array
{
    $result = legacyDbSelect($query, $bindings);

    return ($result[0] ?? null) ? (array) $result[0] : null;
}

function legacyDbSelect(string $query, array $bindings = []): array
{
    return DB::select($query, $bindings);
}

function legacyDbStatement(string $query, array $bindings = []): bool
{
    return DB::statement($query, $bindings);
}

function diffMinutesRemainingStatement(string $column, string $alias): string
{
    return match (DB::getDriverName()) {
        'sqlite' => "ROUND((JULIANDAY($column) - JULIANDAY(CURRENT_TIMESTAMP)) * 1440) AS $alias",
        // mysql
        default => "TIMESTAMPDIFF(MINUTE, NOW(), $column) AS $alias",
    };
}

function diffMinutesPassedStatement(string $column, string $alias): string
{
    return match (DB::getDriverName()) {
        'sqlite' => "ROUND((JULIANDAY(CURRENT_TIMESTAMP) - JULIANDAY($column)) * 1440) AS $alias",
        // mysql
        default => "TIMESTAMPDIFF(MINUTE, $column, NOW()) AS $alias",
    };
}

function floatDivisionStatement(string $dividend, string $divisor): string
{
    return match (DB::getDriverName()) {
        'sqlite' => "(CAST($dividend AS REAL) / $divisor)",
        // mysql
        default => "($dividend / $divisor)",
    };
}

function unixTimestampStatement(string $column, string $alias): string
{
    return match (DB::getDriverName()) {
        'sqlite' => "strftime('%s', $column) AS $alias",
        // mysql
        default => "UNIX_TIMESTAMP($column) AS $alias",
    };
}

function timestampAddMinutesStatement(string $column, int $minutes): string
{
    return match (DB::getDriverName()) {
        'sqlite' => "datetime($column, '" . ($minutes > 0 ? '+' : '-') . abs($minutes) . " minutes')",
        // mysql
        default => "TIMESTAMPADD(MINUTE, $minutes, $column)",
    };
}

function ifStatement(string $condition, mixed $trueValue, mixed $falseValue): string
{
    return match (DB::getDriverName()) {
        'sqlite' => "CASE WHEN $condition THEN $trueValue ELSE $falseValue END",
        // mysql
        default => "IF($condition, $trueValue, $falseValue)"
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
function sanitize_sql_inputs(int|string|null &...$inputs): void
{
    $db = getMysqliConnection();

    foreach ($inputs as &$input) {
        if (!empty($input)) {
            $input = mysqli_real_escape_string($db, (string) $input);
        }
    }
}

/**
 * @deprecated
 */
function s_mysql_query(string $query): mysqli_result|bool
{
    $db = getMysqliConnection();

    $start = microtime(true);

    $result = mysqli_query($db, $query);

    $elapsed = round((microtime(true) - $start) * 1000, 2);

    DB::logQuery($query, [], $elapsed);

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
