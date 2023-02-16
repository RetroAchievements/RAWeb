<x-section class="top-links">
    <a href="/globalRanking.php?s=5&t=2">
        <span class="text-yellow-500"><x-fas-medal/></span>
        Global Ranking
    </a>
    <a href="{{ route('download.index') }}">
        <span class="text-purple-400"><x-fas-gamepad/></span>
        Download Emulator
    </a>
    @if(config('services.discord.invite_id'))
        <a href="https://discord.gg/{{ config('services.discord.invite_id') }}">
            <span class="text-discord"><x-fab-discord/></span>
            Join us on Discord
        </a>
    @endif
    @if(config('services.patreon.user_id'))
        <a href="https://www.patreon.com/bePatron?u={{ config('services.patreon.user_id') }}">
            <span class="text-patreon"><x-fab-patreon/></span>
            Become a Patron
        </a>
    @endif
    <div class="grid grid-cols-4">
        <a href="https://news.retroachievements.org/">
            <span><x-fas-newspaper/></span>
            RANews
        </a>
        <a href="https://www.youtube.com/channel/UCIGdJGxrzmNYMaAGPsk2sIA">
            <span><x-fas-microphone/></span>
            RAPodcast
        </a>
        <a href='https://docs.retroachievements.org/'>
            <span><x-fas-book/></span>
            Docs
        </a>
        <a href='https://docs.retroachievements.org/FAQ/'>
            <span><x-fas-question-circle/></span>
            FAQ
        </a>
    </div>
</x-section>
