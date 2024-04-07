@props([
    'name' => 'hiddenControl',
])

<button id="{{ $name }}-toggle-button" class="absolute bottom-2 right-0 mb-2 btn" onclick="toggle{{ $name }}()">
    {{ $slot }} ▼
</button>
