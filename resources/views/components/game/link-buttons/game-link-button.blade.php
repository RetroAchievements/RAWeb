@props([
    'icon' => '',
    'href' => '#',
])

<li>
    <a class="btn py-2 pr-3 block transition-transform lg:active:scale-[97%]" href="{{ $href }}">
        <span class="icon icon-md mx-1">{{ $icon }}</span>
        {{ $slot }}
    </a>
</li>
