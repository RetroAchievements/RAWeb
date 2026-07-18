<?php

use Illuminate\Support\Facades\DB;

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

function dateCompareStatement(string $column1, string $column2, string $comparison): string
{
    // column1 - column2
    return match (DB::getDriverName()) {
        'sqlite' => "ROUND(JULIANDAY($column1) - JULIANDAY($column2)) $comparison",
        // mysql
        default => "DATEDIFF($column1, $column2) $comparison",
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

function toUnsignedStatement(string $column): string
{
    return match (DB::getDriverName()) {
        'sqlite' => "CASE WHEN $column < 0 THEN $column + 4294967296 ELSE $column END",
        // mysql
        default => "CAST($column AS UNSIGNED)",
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

function greatestStatement(array $columns): string
{
    $columnCSV = implode(',', $columns);

    return match (DB::getDriverName()) {
        'sqlite' => "MAX($columnCSV)",
        // mysql
        default => "GREATEST($columnCSV)",
    };
}

function ifStatement(string $condition, mixed $trueValue, mixed $falseValue): string
{
    return match (DB::getDriverName()) {
        'sqlite' => "IIF($condition, $trueValue, $falseValue)",
        // mysql
        default => "IF($condition, $trueValue, $falseValue)",
    };
}
