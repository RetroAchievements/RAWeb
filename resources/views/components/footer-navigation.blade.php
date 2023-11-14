<x-container>
    <x-menu.footer />
    <div class="mt-4">
        <div class="flex mb-3 items-center">
            <div class="">
                {{ __('Theme') }}
            </div>
            <div class="flex align-center ml-2 gap-2">
                <x-settings.theme-select />
                <x-settings.scheme-select />
            </div>
        </div>
        <div class="flex mb-2 justify-start">
            <div>&copy; 2012-{{ date('Y') }} {{ config('app.name') }}</div>
            <div class="ml-1">
                &middot;
                <a
                    target="_blank"
                    rel="noreferrer"
                    href="https://github.com/RetroAchievements/RAWeb/releases/latest"
                >
                    v{{ config('app.version') }}
                </a>
            </div>
        </div>
    </div>
</x-container>
