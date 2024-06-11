<?php

use Illuminate\Support\Facades\Route;

$menu = collect([
    [
        'title' => __('Documentation'),
        'items' => [
            [
                'label' => __('Developers Code of Conduct'),
                'url' => 'https://docs.retroachievements.org/guidelines/developers/code-of-conduct.html',
                'visible' => true,
            ],
            [
                'label' => __('Users Code of Conduct'),
                'url' => 'https://docs.retroachievements.org/guidelines/users/code-of-conduct.html',
                'visible' => true,
            ],
            [
                'label' => __('FAQ'),
                'url' => 'https://docs.retroachievements.org/general/faq.html',
                'visible' => true,
            ],
            [
                'label' => __('API'),
                'url' => 'https://api-docs.retroachievements.org',
                'visible' => true,
            ],
        ],
    ],
    [
        'title' => __('Community'),
        'items' => [
            [
                'active' => Route::is('ranking*'),
                'label' => __res('ranking'),
                'url' => url('globalRanking.php'),
                // 'url' => route('user.index'),
                'visible' => true,
            ],
            [
                'label' => 'RANews',
                'url' => 'https://news.retroachievements.org',
                'visible' => true,
            ],
            [
                'active' => Route::is('forum*'),
                'label' => __res('forum'),
                'url' => url('forum.php'),
                // 'url' => route('forum-category.show', 1),
                'visible' => true,
            ],
            [
                'active' => Route::is('user*'),
                'label' => __res('user'),
                'url' => url('userList.php'),
                // 'url' => route('user.index'),
                'visible' => true,
            ],
            // [
            //     'active' => Route::is('news*'),
            //     'label' => __res('news'),
            //     'url' => route('news.index'),
            //     'visible' => true,
            // ],
        ],
    ],
    [
        'title' => config('app.name'),
        'items' => [
            [
                'active' => Route::is('contact'),
                'label' => __('Contact Us'),
                'url' => route('contact'),
                'visible' => true,
            ],
            [
                'active' => Route::is('terms'),
                'label' => __('Privacy Policy'),
                'url' => route('terms') . '#privacy-policy',
                'visible' => true,
            ],
            [
                'active' => Route::is('terms'),
                'label' => __('Legal'),
                'url' => route('terms') . '#conditions',
                'visible' => true,
            ],
            // [
            //     'active' => Route::is('feedback'),
            //     'label' => __('Feedback'),
            //     'url' => route('feedback'),
            //     'visible' => true,
            // ],
        ],
    ],
])->map(function ($group) {
    $group['items'] = collect($group['items'] ?? [])
        ->filter(fn ($item) => $item['visible'] ?? true)
        ->map(function ($item) {
            // $item['external'] = !Str::startsWith($item['url'], config('app.url'));
            $item['active'] ??= false;

            return $item;
        });

    return $group;
})
?>
<div class="md:flex flex-wrap flex-row justify-between items-start">
    @foreach ($menu as $menuGroup)
        <div class="mb-3 lg:mb-0 mr-lg">
            <div class="mb-2"><span class="text-lg">{{ $menuGroup['title'] }}</span></div>
            @foreach ($menuGroup['items'] ?? [] as $menuItem)
                <div>
                    <x-link
                        :active="$menuItem['active']"
                        :href="$menuItem['url']"
                    >
                        {{ $menuItem['label'] }}
                    </x-link>
                </div>
            @endforeach
        </div>
    @endforeach
    
    <div class="mb-3 lg:mb-0">
        <div class="mb-2"><span class="text-lg">Connect</span></div>
        <div class="flex gap-3 text-lg">
            @if(config('services.patreon.user_id'))
                <a href="https://www.patreon.com/bePatron?u={{ config('services.patreon.user_id') }}"
                   data-toggle="tooltip" title="Become a patron" rel="noopener">
                    <x-fab-patreon/>
                    <span class="sr-only">Patreon</span>
                </a>
            @endif
            @if(config('services.discord.invite_id'))
                <a href="https://discord.gg/{{ config('services.discord.invite_id') }}"
                   data-toggle="tooltip" title="Join us on Discord" rel="noopener">
                    <x-fab-discord/>
                    <span class="sr-only">Discord</span>
                </a>
            @endif
            @if(config('services.github.organisation'))
                <a href="https://github.com/{{ config('services.github.organisation') }}"
                   data-toggle="tooltip" title="Contribute on GitHub" rel="noopener">
                    <x-fab-github/>
                    <span class="sr-only">GitHub</span>
                </a>
            @endif
            @if(config('services.twitch.channel'))
                <a href="https://twitch.tv/{{ config('services.twitch.channel') }}"
                   data-toggle="tooltip" title="Follow us on Twitch" rel="noopener">
                    <x-fab-twitch/>
                    <span class="sr-only">Twitch</span>
                </a>
            @endif
            @if(config('services.facebook.channel'))
                <a href="https://facebook.com/{{ config('services.facebook.channel') }}/"
                   data-toggle="tooltip" title="Follow us on Facebook" rel="noopener">
                    <x-fab-facebook/>
                    <span class="sr-only">Facebook</span>
                </a>
            @endif
            @if(config('services.threads.channel'))
                <a
                    href="{{ 'https://threads.net/@' . config('services.threads.channel') }}"
                    data-toggle="tooltip"
                    title="Follow us on Threads"
                    rel="noopener"
                >
                    <x-fab-threads />
                    <span class="sr-only">Threads</span>
                </a>
            @endif

            {{-- @if(config('services.twitter.channel'))
                <a href="https://twitter.com/{{ config('services.twitter.channel') }}"
                   data-toggle="tooltip" title="Follow us on Twitter" rel="noopener">
                    <x-fab-twitter/>
                    <span class="sr-only">Twitter</span>
                </a>
            @endif --}}
            
            @if(config('services.reddit.channel'))
                <a href="https://reddit.com/r/{{ config('services.reddit.channel') }}"
                   data-toggle="tooltip" title="Follow us on Reddit" rel="noopener">
                    <x-fab-reddit/>
                    <span class="sr-only">Reddit</span>
                </a>
            @endif
            {{--<a href="{{ route('rss.index') }}"--}}
            <a href="{{ route('rss.index') }}"
               data-toggle="tooltip" title="RSS Feeds">
                <x-fas-rss/>
                <span class="sr-only">RSS</span>
            </a>
        </div>
    </div>
</div>
