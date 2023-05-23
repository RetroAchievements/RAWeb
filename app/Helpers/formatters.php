<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;

if (!function_exists('localized_date')) {
    function localized_date(
        ?Carbon $dateTime = null,
        string $locale = null,
        int $dateType = IntlDateFormatter::SHORT,
        int $timeType = IntlDateFormatter::SHORT,
        ?string $timezone = 'UTC'
    ): string {
        $dateTime ??= Carbon::now();
        $locale ??= session('date_locale');
        $timezone ??= session('timezone') ?? 'UTC';

        $dateFormatter = new IntlDateFormatter($locale, $dateType, IntlDateFormatter::NONE, $timezone);
        $timeFormatter = new IntlDateFormatter($locale, IntlDateFormatter::NONE, $timeType, $timezone);

        return ($dateType !== IntlDateFormatter::NONE ? $dateFormatter->format($dateTime) : '')
            . ($dateType !== IntlDateFormatter::NONE && $timeType !== IntlDateFormatter::NONE ? ', ' : '')
            . ($timeType !== IntlDateFormatter::NONE ? $timeFormatter->format($dateTime) : '');
    }
}

if (!function_exists('localized_number')) {
    function localized_number(
        int|float $number,
        string $locale = null,
        int $format = NumberFormatter::DECIMAL,
        int $fractionDigits = 0,
        string $pattern = null
    ): string {
        $locale = $locale ?? session('number_locale', 'en_US');

        $formatter = new NumberFormatter($locale, $format, $pattern);
        $formatter->setAttribute($formatter::FRACTION_DIGITS, $fractionDigits);

        return $formatter->format($number);
    }
}
