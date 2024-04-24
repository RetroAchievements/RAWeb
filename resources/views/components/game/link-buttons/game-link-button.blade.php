@props([
    'icon' => '',
    'href' => '#',
])

<li>
    <a class="btn py-2 w-full px-3 inline-flex gap-x-2 transition-transform lg:active:scale-[97%]" href="{{ $href }}">
        <span>{{ $icon }}</span>
        <span>{{ $slot }}</span>
    </a>
</li>
