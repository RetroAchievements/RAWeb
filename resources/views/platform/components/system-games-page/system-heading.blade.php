@props([
    'systemId' => 1,
    'systemName' => '',
])

@php
    $systemIconUrl = getSystemIconUrl($systemId);
@endphp

<h2 class="flex gap-x-2 items-center $headingSizeClassName">
    <img src="{{ $systemIconUrl }} " alt="{{ $systemName }} icon" width="32" height="32">
    <span>{{ $systemName }}</span>
</h2>
