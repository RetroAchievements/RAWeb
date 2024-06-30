@props([
    'class' => 'absolute bottom-4 right-0 btn' // TODO default this to "btn"
])

<button
    id="hidden-controls-toggle-button"
    type="button"
    class="{{ $class }}"
    onclick="toggleExpander('hidden-controls-toggle-button', 'hidden-controls-content')"
>
    {{ $slot }} ▼
</button>
