<?php

use Illuminate\Support\Carbon;

/** @var Carbon $dateTime */
$dateTime ??= $value ?? null;

$locale ??= session('date_locale');
$timezone ??= session('timezone') ?? 'UTC';

$short ??= false;
$shortDate ??= $short ?? false;
$seconds ??= $short ?? false;
$time ??= false;
$tooltip ??= true;
?>
<x-datetime :dateTime="$dateTime"
            :locale="$locale"
            :timezone="$timezone"
            :date="true"
            :time="$time"
            :short="$short"
            :shortDate="$shortDate"
            :seconds="$seconds"
            :tooltip="$tooltip"
/>
