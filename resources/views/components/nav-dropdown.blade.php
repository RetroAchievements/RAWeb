@props([
    'active' => false,
    'class' => '',
    'desktopHref' => null,
    'dropdownClass' => '',
    'title' => '',
    'trigger' => '',
    'triggerClass' => '',
])

<x-dropdown
    class="nav-item {{ $class ?? '' }}"
    trigger-class="nav-link {{ $triggerClass ?? '' }}"
    :dropdown-class="$dropdownClass ?? ''"
    :active="$active ?? false"
    :title="$title ?? ''"
    :desktopHref="$desktopHref"
>
    <x-slot name="trigger">{{ $trigger }}</x-slot>
    {{ $slot }}
</x-dropdown>
