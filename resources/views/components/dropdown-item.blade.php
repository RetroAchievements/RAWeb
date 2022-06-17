@if($link ?? false)
    <x-link class="dropdown-item"
            :active="$active ?? null"
            :external="$external ?? null"
            :link="$link">
        {{ $slot }}
    </x-link>
@else
    <span class="dropdown-item {{ ($active ?? false) ? 'active' : '' }}">
        {{ $slot }}
    </span>
@endif
