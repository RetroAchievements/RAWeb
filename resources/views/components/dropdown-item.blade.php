@if($link ?? false)
    <x-link class="dropdown-item {{ $class ?? null }}"
            :active="$active ?? null"
            :external="$external ?? null"
            :link="$link">
        {{ $slot }}
    </x-link>
@else
    <span class="dropdown-item {{ $class ?? null }} {{ ($active ?? false) ? 'active' : '' }}">
        {{ $slot }}
    </span>
@endif
