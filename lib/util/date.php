<?php

function formatHMS(int $seconds): string
{
    return sprintf("%d:%02d:%02d", $seconds / 3600, ($seconds / 60) % 60, $seconds % 60);
}

function getNiceTime($timestamp, $locale = 'EN-GB'): string
{
    setlocale(LC_ALL, $locale);

    return strftime("%H:%M", $timestamp);
}

function getNiceDate($timestamp, $justDay = false, $locale = 'EN-GB'): string
{
    setlocale(LC_ALL, $locale);

    $todayTimestampDate = strtotime(date('F j, Y'));
    $yesterdayTimestampDate = strtotime(date("F j, Y", time() - 60 * 60 * 24));

    // Convert timestamp to day
    $timestampDate = strtotime(date('F j, Y' . $timestamp));

    if ($timestampDate == $todayTimestampDate) {
        $dateOut = 'Today';
    } else {
        if ($timestampDate == $yesterdayTimestampDate) {
            $dateOut = 'Yesterday';
        } else {
            $dateOut = strftime("%d %b %Y", $timestamp);
        }
    }

    if (!$justDay) {
        $dateOut .= strftime(", %H:%M", $timestamp);
    }

    return $dateOut;
}
