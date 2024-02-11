@props([
    'class' => null,
    'style' => null,
])

<section class="{{ $class }}" style="{{ $style }}">
    {{ $slot }}
</section>
