<x-section class="mb-3 flex flex-col">
    <a class="btn flex justify-center gap-1.5 p-3 text-xs" href="/globalRanking.php?s=5&t=2">
        <span class="text-yellow-500"><x-fas-medal/></span>
        Global Ranking
    </a>
    <a class="btn flex justify-center gap-1.5 p-3 text-xs" href="{{ route('download.index') }}">
        <span class="text-purple-400"><x-fas-gamepad/></span>
        Download Emulator
    </a>
    @if(config('services.discord.invite_id'))
        <a class="btn flex justify-center gap-1.5 p-3 text-xs" href="https://discord.gg/{{ config('services.discord.invite_id') }}">
            <span class="text-discord"><x-fab-discord/></span>
            Join us on Discord
        </a>
    @endif
    @if(config('services.patreon.user_id'))
        <a class="btn flex justify-center gap-1.5 p-3 text-xs" href="https://www.patreon.com/bePatron?u={{ config('services.patreon.user_id') }}">
            <span class="text-patreon"><x-fab-patreon/></span>
            Become a Patron
        </a>
    @endif
    <div class="grid grid-cols-4">
        <a class="btn flex flex-col items-center gap-3 p-3 text-xs" href="https://news.retroachievements.org/">
            <span class="text-gray-400"><x-fas-newspaper/></span>
            RANews
        </a>
        <a class="btn flex flex-col items-center gap-3 p-3 text-xs" href="https://www.youtube.com/channel/UCIGdJGxrzmNYMaAGPsk2sIA">
            <span class="text-gray-400"><x-fas-microphone/></span>
            RAPodcast
        </a>
        <a class="btn flex flex-col items-center gap-3 p-3 text-xs" href='https://docs.retroachievements.org/'>
            <span class="text-gray-400"><x-fas-book/></span>
            Docs
        </a>
        <a class="btn flex flex-col items-center gap-3 p-3 text-xs" href='https://docs.retroachievements.org/FAQ/'>
            <span class="text-gray-400"><x-fas-question-circle/></span>
            FAQ
        </a>
    </div>
</x-section>
