<x-dropdown
    class="nav-item {{ $class ?? '' }}"
    trigger-class="nav-link {{ $triggerClass ?? '' }}"
    dropdown-class="{{ $dropdownClass ?? '' }}"
    :active="$active ?? false"
>
    <x-slot name="trigger">{{ $trigger }}</x-slot>
    {{ $slot }}
</x-dropdown>
