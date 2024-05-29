{{--
    TODO this should eventually fall through to datetime.blade.php,
    or maybe that component should have a "relative" prop. 
    let's figure it out when efforts towards localization are accelerated.
--}}

@props([
    'isAbsolute' => false,
    'postedAt' => '',
])

<?php

use Illuminate\Support\Carbon;

$label = 
    $isAbsolute
        ? getNiceDate(strtotime($postedAt))
        : Carbon::parse($postedAt)->diffForHumans();

?>

{{ $label }}
