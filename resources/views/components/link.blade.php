<?php

use Illuminate\Support\Str;

$external ??= !Str::startsWith($link ?? null, config('app.url'));
$active ??= Str::startsWith(url()->current(), $link ?? null);
?>
<a class="{{ $class ?? '' }} {{ ($active ?? false) ? 'active' : '' }}" target="{{ $target ?? '_self' }}" href="{{ $link ?? null }}" {!! ($external ?? false) ? 'rel="noopener"' : '' !!} title="{{ $title ?? '' }}">
    {{ $slot }}
    @if($external ?? false)
        <x-fas-external-link-alt />
    @endif
</a>
