@props([
    'tooltip' => '',
])

<?php
// If we don't do this, the Blade renderer will truncate the
// entire string after any white space. This is probably due to
// mounting the component's parent in a legacy context.
$encodedTooltip = $tooltip ? 'title=' . json_encode($tooltip) : '';
?>

<span
    {!! $encodedTooltip !!}
    class='{{ $tooltip ? 'cursor-help' : '' }} px-1 text-2xs font-semibold border border-text rounded-full min-w-max'
>
    {{ $slot }}
</span>
