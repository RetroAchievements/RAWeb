@props([
    'class' => '',
    'active' => false,
    'triggerClass' => '',
    'title' => '',
    'dropdownClass' => '',
    'trigger' => '',
    'desktopHref' => null, // string | null
])

<?php
use Jenssegers\Agent\Agent;

$id = uniqid();

$agent = new Agent();
$canUseDesktopHref = !$agent->isMobile();
?>

<div class="dropdown {{ $class ?? '' }} {{ ($active ?? false) ? 'active' : '' }}">
    <x-dropdown-trigger
        triggerClass="{{ $triggerClass ?? '' }}"
        id="dropdownTrigger{{ $id }}"
        title="{{ $title ?? '' }}"
        :desktopHref="($desktopHref && $canUseDesktopHref) ? $desktopHref : null"
    >
        {{ $trigger }}
    </x-dropdown-trigger>

    <div class="dropdown-menu {{ $dropdownClass ?? '' }}" aria-labelledby="dropdownTrigger{{ $id }}">
        {{ $slot }}
    </div>
</div>
