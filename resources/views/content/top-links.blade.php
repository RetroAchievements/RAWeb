<x-section class="mb-3 flex flex-col gap-2">
    <a class="btn text-center py-2" href="/globalRanking.php?s=5&t=2">
        <x-fas-medal/>
        Global Ranking
    </a>
    <a class="btn text-center py-2" href="{{ route('download.index') }}">
        <x-fas-download/>
        Download Emulator
    </a>
    @if(config('services.discord.invite_id'))
        <a class="btn text-center py-2" href="https://discord.gg/{{ config('services.discord.invite_id') }}">
            <x-fab-discord/>
            Join us on Discord
        </a>
    @endif
    @if(config('services.patreon.user_id'))
        <a class="btn text-center py-2" href="https://www.patreon.com/bePatron?u={{ config('services.patreon.user_id') }}">
            <x-fab-patreon/>
            Become a Patron
        </a>
    @endif
    <div class="flex gap-1">
        <a class="btn text-center py-2 grow" href="https://news.retroachievements.org/">
            <x-fas-newspaper/>
            RANews
        </a>
        <a class="btn text-center py-2 grow" href="https://www.youtube.com/channel/UCIGdJGxrzmNYMaAGPsk2sIA">
            <x-fas-microphone/>
            RAPodcast
        </a>
    </div>
    <div class="flex gap-1">
        <a class="btn text-center py-2 grow" href='https://docs.retroachievements.org/'>
            <x-fas-book/>
            Documentation
        </a>
        <a class="btn text-center py-2 grow" href='https://docs.retroachievements.org/FAQ/'>
            FAQ
        </a>
    </div>
</x-section>
