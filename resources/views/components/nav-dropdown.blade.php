@props([
    'active' => false,
    'class' => '',
    'desktopHref' => null,
    'dropdownClass' => '',
    'title' => '',
    'trigger' => '',
    'triggerClass' => '',
    'forceHref' => false,
])

<x-dropdown
    class="nav-item {{ $class ?? '' }}"
    trigger-class="nav-link {{ $triggerClass ?? '' }}"
    :dropdown-class="$dropdownClass ?? ''"
    :active="$active ?? false"
    :title="$title ?? ''"
    :desktopHref="$desktopHref"
    :force-href="$forceHref"
>
    <x-slot name="trigger">{{ $trigger }}</x-slot>
    {{ $slot }}
</x-dropdown>
