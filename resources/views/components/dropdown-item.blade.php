@props([
    'href' => null, // ?string
    'active' => null, // ?bool
    'external' => false,
    'class' => null, // ?string
])

@if ($href)
    <x-link
        class="dropdown-item {{ $class }}"
        :active="$active"
        :external="$external"
        :href="$href"
    >
        {{ $slot }}
    </x-link>
@else
    <span class="dropdown-item {{ $class }} {{ ($active) ? 'active' : '' }}">
        {{ $slot }}
    </span>
@endif
