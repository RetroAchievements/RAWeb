<x-section>
    <div class="bg-embed p-4 border-text rounded">
        <h2>{{ __('Welcome!') }}</h2>

        <p class="mb-2">
            Were you the greatest in your day at Mega Drive, SNES, or PlayStation 2 games? Wanna prove it? Use our
            <a href="{{ route('download.index') }}">supported emulators</a> and you will obtain achievements and badges as
            you play! Your progress will be tracked so you can compete with your friends to complete all your favorite
            classics to 100%: we provide <a href="{{ route('download.index') }}">the emulators</a>,
            all you need are the <a href="{{ route('game.index') }}">games</a>!
        </p>

        <p>
            <a href="{{ route('game.show', 1) }}">Which of these achievements do you think you can get?</a>
        </p>
    </div>
</x-section>
