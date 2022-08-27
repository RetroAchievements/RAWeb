<nav class="z-20 {{ $class }}">
    <x-container :fluid="$fluid">
        <div class="flex items-center">
            {{ $brand ?? null }}
            {{--<div class="hidden {{ "$breakpoint:block" }}">--}}
            <div class="flex-1 hidden {{ "$breakpoint:block" }}">
                {{ $slot }}
            </div>
            {{ $right ?? null }}
        </div>
    </x-container>
</nav>
{{--<nav class="z-20 {{ $class }} overflow-auto navbar-scrollable {{ $breakpoint }}:hidden">--}}
<nav class="z-20 {{ $class }} {{ $breakpoint }}:hidden">
    <x-container :fluid="$fluid">
        {{ $mobile ?? $slot }}
    </x-container>
</nav>
