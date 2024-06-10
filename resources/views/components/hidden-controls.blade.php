@props([
    "class" => "hidden py-2 px-4 -mx-5 -mt-3 sm:-mt-1.5 mb-4", // TODO default this to "hidden"
    "innerClass" => "mx-1 -my-2 bg-embed p-4 rounded" // TODO remove "-my-2" to be more reusable
])

<div
    id="hidden-controls-content"
    class="{{ $class }}"
>
    <div class="{{ $innerClass }}">
        {{ $slot }}
    </div>
</div>
