<x-app-layout :page-title="__('API')">
    <div class="">
        <x-section>
            <h2>RetroAchievements Official API</h2>

            <p>
                Here you can find links to our current API offerings for pulling
                RetroAchievements data into your apps and services.
            </p>
        </x-section>

        <div class="grid gap-4 grid-cols-2">
            <div class="group transition p-4 w-full cursor-pointer text-link bg-embed border border-embed-highlight hover:text-white hover:border-menu-link hover:bg-embed-highlight select-none rounded">
                <div class="flex flex-col gap-y-2">
                    <p class="text-lg">JavaScript API</p>
                    <p>
                        Effortlessly fetch achievement, user, and game data while enjoying a modular design,
                        tree-shaking support, and seamless migration to API v2. Designed for Node environments (16+),
                        it includes TypeScript support, type mappings, and a compact size of less than 3KB.
                    </p>

                    <div class="block">
                    <div class="bg-bg flex gap-x-2 items-center">
                        <span>Get Started</span>
                        <x-fas-external-link-alt />
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

@section('header')
    <x-page-header>
        <x-slot name="title"><h2>RetroAchievements API</h2></x-slot>
        <x-slot name="subTitle">
            <p class="lead">
                Resources for Connect & Web APIs.<br>
                Make sure that your application uses the latest stable version that is currently available.
            </p>
        </x-slot>
    </x-page-header>
@endsection

@section('main')
    <div class="md:grid grid-flow-col gap-4">
        <div class="lg:col-6">
            <x-section>
                <x-section-header>
                    <x-slot name="title"><h3>Web API</h3></x-slot>
                </x-section-header>
                <p>
                    You can find your personal API token to access public data in your
                    <a href="#">settings</a>.<br>
                    This is a beta offering and only supports read-only access to data that can already be found on the site.<br>
                    No personal/user data can be accessed beyond what is already publicly accessible for
                    every user (username, avatar, motto and activity).
                </p>
                <x-link class="btn btn-primary" link="https://github.com/retroachievements/web-api-client-php" target="_blank">PHP Client</x-link>
            </x-section>
        </div>
        <div class="lg:col-6">
            <x-section>
                <x-section-header>
                    <x-slot name="title"><h3>Connect API</h3></x-slot>
                </x-section-header>
                <p>
                    RPC APIs to be consumed by emulators, toolkits and other third party applications. Used by various clients.
                </p>
                <x-link class="btn btn-primary" :link="asset('docs/api/connect/index.html')" target="_blank">{{ __('Documentation') }}</x-link>
            </x-section>
        </div>
    </div>
@endsection