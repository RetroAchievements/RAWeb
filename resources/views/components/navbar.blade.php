<nav class="z-20 {{ $class }}">
    <x-container :fluid="$fluid">
        <div class="flex items-center bg-embedded flex-wrap">
            {{ $brand ?? null }}
            <div class="flex-1 mx-2 hidden {{ "$breakpoint:flex" }}">
                {{ $slot }}
            </div>
            {{ $right ?? null }}
        </div>
    </x-container>
</nav>
{{--<nav class="z-20 {{ $class }} overflow-auto navbar-scrollable {{ $breakpoint }}:hidden">--}}
<nav class="z-10 {{ $class }} {{ $breakpoint }}:hidden">
    <x-container :fluid="$fluid">
        <div class="flex items-center bg-embedded">
            {{ $mobile ?? $slot }}
        </div>
    </x-container>
</nav>
