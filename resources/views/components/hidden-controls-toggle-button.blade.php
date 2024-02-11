@props([
    'label' => 'Moderate', // string
])

<button id="hidden-controls-toggle-button" class="absolute bottom-2 right-0 mb-2 btn" onclick="toggleHiddenControls()">
    {{ $label }} ▼
</button>
