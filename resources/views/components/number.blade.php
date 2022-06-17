<?php
$number ??= $value ?? 0;

$locale ??= session('number_locale');

$format = NumberFormatter::DECIMAL;
$fractionDigits ??= 0;
$pattern ??= null;
$percent ??= false;
if ($percent) {
    $format = NumberFormatter::PERCENT;
    $fractionDigits ??= 2;
}
?>
{{ localized_number($number, $locale, $format, $fractionDigits, $pattern) }}
