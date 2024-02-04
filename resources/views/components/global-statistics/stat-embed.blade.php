@props([
    'label' => '',
    'count' => 0,
    'href' => '#',
])

<a class="stat-box" href="{{ $href }}">
    <span class="text-xs">{{ $label }}</span>
    <span class="text-xl">{{ number_format($count) }}</span>
</a>
