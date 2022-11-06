<?php

use Illuminate\Support\Carbon;

/* @var Carbon $dateTime */
$dateTime ??= $value ?? null;

$locale ??= session('date_locale');
$timezone ??= session('timezone') ?? 'UTC';

$short ??= false;
$shortDate ??= $short ?? false;
$seconds ??= $short ?? false;
$tooltip ??= true;
?>
<x-datetime :dateTime="$dateTime"
            :locale="$locale"
            :timezone="$timezone"
            :date="false"
            :time="true"
            :short="$short"
            :shortDate="$shortDate"
            :seconds="$seconds"
            :tooltip="$tooltip"
/>
