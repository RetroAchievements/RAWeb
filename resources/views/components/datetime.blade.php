<?php

use Illuminate\Support\Carbon;

/** @var Carbon $dateTime */
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
$title = '';
if ($tooltip) {
    $title = $timezone !== 'UTC' ? $timezone . '<br>' : '';
    $title .= '<span class="text-secondary">' . localized_date($dateTime, $locale, $dateTypeDefault, $timeType, 'UTC') . ', UTC</span>';
}
?>
<span style="cursor:help" data-toggle="{{ $tooltip ? 'tooltip' : '' }}" title="{{ $title }}">
    {{ localized_date($dateTime, $locale, $dateType, $timeType, $timezone) }}
</span>
