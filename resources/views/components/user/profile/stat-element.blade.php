@props([
    'href' => null, // ?string
    'hrefLabel' => null, // ?string
    'isHrefLabelBeforeLabel' => true,
    'isMuted' => false,
    'label' => 'Label',
    'shouldEnableBolding' => true,
    'value' => 0,
    'weightedPoints' => null,
])

<div class="relative flex w-full items-center justify-between text-2xs">
    <p class="z-[2] bg-embed pr-2">{{ $label }}</p>

    <div class="absolute left-0 right-0 border-t border-dotted border-text-muted"></div>

    <p class="z-[2] bg-embed pl-2 {{ $isMuted ? 'text-muted italic' : '' }} {{ $shouldEnableBolding && !$isMuted ? 'font-bold' : '' }}">
        @if ($href)
            {{-- Allows for only fragments to be linked --}}
            @if (!$isHrefLabelBeforeLabel && $hrefLabel && $value)
                <span>{{ $value }}</span>
            @endif

            <a href="{{ $href }}">
                {{ $hrefLabel ?? $value }}
            </a>

            {{-- Allows for only fragments to be linked --}}
            @if ($isHrefLabelBeforeLabel && $hrefLabel && $value)
                <span>{{ $value }}</span>
            @endif
        @elseif (!$hrefLabel && isset($value))
            {{ $value }}
            @if ($weightedPoints)
                <x-points-weighted-container>
                    ({{ localized_number($weightedPoints) }})
                </x-points-weighted-container>
            @endif
        @endif
    </p>
</div>
