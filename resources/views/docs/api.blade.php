<x-app-layout :page-title="__('API')">
    <x-section class="mb-8">
        <h2>RetroAchievements API</h2>

        <div class="grid gap-y-2">
            <p>
                Here you can find links to our current API offerings for integrating
                RetroAchievements data into your apps and services.
            </p>

            <p>
                You can find your personal API token to access public data in your
                <a href="/controlpanel.php">settings</a>. Our API implementations only support
                read-only access to data that can already be found on the site. No personal/user
                data can be accessed beyond what is already publicly accessible for every user.
            </p>
        </div>
    </x-section>

    <div class="grid gap-4 lg:grid-cols-2">
        <a
            href="https://api-docs.retroachievements.org"
            target="_blank"
            rel="noreferrer"
            class="group transition p-4 w-full cursor-pointer text-link bg-embed border border-embed-highlight hover:text-white hover:border-menu-link hover:bg-embed-highlight select-none rounded"
        >
            <div class="flex flex-col gap-y-2">
                <p class="text-lg flex items-center gap-x-1">
                    <span>JavaScript API</span>
                    <span class="transition-transform group-hover:translate-x-1">
                        <x-fas-external-link-alt />
                    </span>
                </p>
                <p>
                    Effortlessly fetch achievement, user, and game data while enjoying a modular design,
                    tree-shaking support, and seamless migration path to API v2. Designed for Node environments (16+),
                    it includes native TypeScript support, type mappings, extensive documentation, and a compact size of less than 3KB.
                </p>
            </div>
        </a>
    </div>
</x-app-layout>
