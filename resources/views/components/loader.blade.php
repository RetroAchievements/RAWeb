<?php
$size ??= 'xs';

$iconSizeClass = 'icon-' . $size;
?>
<div class="loader" role="alert" aria-live="assertive" aria-atomic="true">
    {{--<x-loader-icon :size="$size" class=""/>--}}
    <x-fas-circle-notch class="icon-spin text-theme {{ $iconSizeClass }}" />
    <span class="sr-only">Loading</span>
</div>
