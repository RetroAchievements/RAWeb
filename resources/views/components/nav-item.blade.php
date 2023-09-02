<div class="nav-item {{ ($active ?? null) ? 'active' : '' }}">
    <x-link class="nav-link" :active="$active ?? null" :link="$link" :external="$external ?? false" :title="$title ?? null">
        {{ $slot }}
    </x-link>
</div>
