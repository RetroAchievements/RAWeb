@php
    $hasBanner = !empty($page['props']['banner']['desktopMdWebp'] ?? null);
@endphp

<nav class="relative z-20 {{ $class }} {{ $hasBanner ? 'has-banner !bg-transparent' : '' }}">
    <x-container :fluid="$fluid">
        <div class="{{ $hasBanner ? '!bg-transparent' : 'bg-embedded' }} flex items-center flex-wrap">
            {{ $brand ?? null }}
            <div class="flex-1 mx-2 hidden {{ "$breakpoint:flex" }}">
                {{ $slot }}
            </div>
            {{ $right ?? null }}
        </div>
    </x-container>
</nav>
{{--<nav class="z-20 {{ $class }} overflow-auto navbar-scrollable {{ $breakpoint }}:hidden">--}}
<nav class="relative z-20 {{ $class }} {{ $hasBanner ? 'has-banner !bg-transparent' : '' }} {{ $breakpoint }}:hidden">
    <x-container :fluid="$fluid">
        <div class="{{ $hasBanner ? '!bg-transparent' : 'bg-embedded' }} flex items-center">
            {{ $mobile ?? $slot }}
        </div>
    </x-container>
</nav>
