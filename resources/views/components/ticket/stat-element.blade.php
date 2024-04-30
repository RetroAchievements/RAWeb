@props([
    'label' => 'Label',
])

<div class="relative flex w-full items-center justify-between text-2xs">
    <p class="z-[2] bg-embed pr-2">{{ $label }}</p>

    <div class="absolute left-0 right-0 border-t border-dotted border-text-muted"></div>

    <p class="z-[2] bg-embed pl-2">
        {{ $slot }}
    </p>
</div>
