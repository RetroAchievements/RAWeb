@props([
    'active' => null, // ?bool
    'external' => false,
    'href' => '',
    'title' => null, // ?string
])

<div class="nav-item {{ ($active) ? 'active' : '' }}">
    <x-link
        class="nav-link"
        :active="$active"
        :href="$href"
        :external="$external"
        :title="$title"
    >
        {{ $slot }}
    </x-link>
</div>
