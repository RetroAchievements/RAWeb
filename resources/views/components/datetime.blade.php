<?php

use Illuminate\Support\Carbon;

/* @var Carbon $dateTime */
$dateTime ??= $value ?? null;

$locale ??= session('date_locale');
$timezone ??= session('timezone') ?? 'UTC';

// $short = $short ?? false;
// $shortDate = $shortDate ?? $short ?? false;
$time ??= true;
$date ??= true;
$seconds ??= $short ?? false;
// $dateTypeDefault = $shortDate ? IntlDateFormatter::SHORT : IntlDateFormatter::MEDIUM;
$dateTypeDefault = IntlDateFormatter::SHORT;
$dateType = $date ? $dateTypeDefault : IntlDateFormatter::NONE;
$timeType = $time ? ($seconds ? IntlDateFormatter::MEDIUM : IntlDateFormatter::SHORT) : IntlDateFormatter::NONE;
$tooltip ??= true;
?>

@if (!$tooltip || $timezone === 'UTC')
    {{ localized_date($dateTime, $locale, $dateType, $timeType, $timezone) }}
@else
    <span style="cursor:help" data-toggle="tooltip"
          title="{{ localized_date($dateTime, $locale, $dateTypeDefault, $timeType, 'UTC') . ' UTC' }}">
        {{ localized_date($dateTime, $locale, $dateType, $timeType, $timezone) }}
    </span>
@endif
