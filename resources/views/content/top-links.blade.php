<x-section class="mb-3 flex flex-col gap-2">
    <a class="btn text-center py-2" href="{{ route('download.index') }}">
        <span class="text-heading"><x-fas-gamepad/></span>
        Download Emulator
    </a>
    <a class="btn text-center py-2" href="/globalRanking.php">
        <span class="text-yellow-400"><x-fas-trophy/></span>
        Global Points Ranking
    </a>
    <a class="btn text-center py-2" href="{{ route('ranking.beaten-games') }}">
        <span class="text-yellow-400"><x-fas-medal/></span>
        Global Beaten Games Ranking
    </a>
    @if(config('services.discord.invite_id'))
        <a class="btn text-center py-2" href="https://discord.gg/{{ config('services.discord.invite_id') }}">
            <span class="text-discord"><x-fab-discord/></span>
            Join us on Discord
        </a>
    @endif
    @if(config('services.patreon.user_id'))
        <a class="btn text-center py-2" href="https://www.patreon.com/bePatron?u={{ config('services.patreon.user_id') }}">
            <span class="text-patreon"><x-fab-patreon/></span>
            Become a Patron
        </a>
    @endif
    <div class="flex gap-1">
        <a class="btn text-center py-2 grow" href="https://news.retroachievements.org/">
            <span class="text-heading"><x-fas-newspaper/></span>
            RANews
        </a>
        <a class="btn text-center py-2 grow" href="https://www.youtube.com/@RAPodcast">
            <span class="text-heading"><x-fas-microphone/></span>
            RAPodcast
        </a>
    </div>
    <div class="flex gap-1">
        <a class="btn text-center py-2 grow" href='https://docs.retroachievements.org/'>
            <span class="text-heading"><x-fas-book/></span>
            Documentation
        </a>
        <a class="btn text-center py-2 grow" href='https://docs.retroachievements.org/FAQ/'>
            <span class="text-heading"><x-fas-question-circle/></span>
            FAQ
        </a>
    </div>
</x-section>
