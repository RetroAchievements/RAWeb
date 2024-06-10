@php
    $containerClassNames = [
        'flex flex-col gap-y-2 gap-x-1',
        '[&>a]:flex [&>a]:items-center [&>a]:justify-center [&>a]:gap-1.5 [&>a]:py-2',
        '[&>a>span]:-mt-px',
    ];
@endphp

<x-section @class($containerClassNames)>
    <a class="btn" href="{{ route('download.index') }}">
        <span class="text-heading"><x-fas-download /></span>
        Download Emulator
    </a>

    <a class="btn" href="/globalRanking.php">
        <span class="text-yellow-400"><x-fas-trophy /></span>
        Global Points Ranking
    </a>

    <a class="btn" href="{{ route('ranking.beaten-games') }}">
        <span class="text-yellow-400"><x-fas-medal /></span>
        Global Beaten Games Ranking
    </a>

    @if (config('services.discord.invite_id'))
        <a class="btn" href="https://discord.gg/{{ config('services.discord.invite_id') }}">
            <span class="text-discord"><x-fab-discord /></span>
            Join us on Discord
        </a>
    @endif

    @if (config('services.patreon.user_id'))
        <a class="btn" href="https://www.patreon.com/bePatron?u={{ config('services.patreon.user_id') }}">
            <span class="text-patreon"><x-fab-patreon /></span>
            Become a Patron
        </a>
    @endif

    <div @class([...$containerClassNames, '!flex-row'])>
        <a class="btn grow" href="https://news.retroachievements.org/">
            <span class="text-heading"><x-fas-newspaper /></span>
            RANews
        </a>
        
        <a class="btn grow" href="https://www.youtube.com/@RAPodcast">
            <span class="text-heading"><x-fas-microphone /></span>
            RAPodcast
        </a>
    </div>

    <div @class([...$containerClassNames, '!flex-row'])>
        <a class="btn grow" href='https://docs.retroachievements.org/'>
            <span class="text-heading"><x-fas-book /></span>
            Documentation
        </a>
        
        <a class="btn grow" href='https://docs.retroachievements.org/general/faq.html'>
            <span class="text-heading"><x-fas-question-circle /></span>
            FAQ
        </a>
    </div>
</x-section>
