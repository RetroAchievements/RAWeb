<x-section>
    <x-section-header>
        <x-slot name="title"><h2>{{ __('Welcome!') }}</h2></x-slot>
    </x-section-header>
    <p class="mb-2">
        Were you the greatest in your day at Mega Drive or SNES games? Wanna prove it? Use our
        <a href="{{ route('download.index') }}">modified emulators</a> and you will obtain achievements and badges as
        you play! Your progress will be tracked so you can compete with your friends to complete all your favourite
        classics to 100%: we provide <a href="{{ route('download.index') }}">the emulators</a>,
        all you need are the <a href="{{ route('game.index') }}">games</a>!
    </p>
    <p>
        <a href="{{ route('game.show', 1) }}">Which of these do you think you can get?</a>
    </p>
</x-section>
