<?php

function getNiceTime($timestamp, $locale = 'EN-GB')
{
    setlocale(LC_ALL, $locale);
    return strftime("%H:%M", $timestamp);
}

function getNiceDate($timestamp, $justDay = false, $locale = 'EN-GB')
{
    setlocale(LC_ALL, $locale);

    $todayTimestampDate = strtotime(date('F j, Y'));
    $yesterdayTimestampDate = strtotime(date("F j, Y", time() - 60 * 60 * 24));

    //	Convert timestamp to day
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

    if ($justDay == false) {
        $dateOut .= strftime(", %H:%M", $timestamp);
    }

    return $dateOut;
}
