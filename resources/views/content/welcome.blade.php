<?php
    $randomGameIds = [
        1, // Sonic the Hedgehog (Mega Drive)
        3, // Streets of Rage 2 (Mega Drive)
        1451, // Mega Man 2 (NES)
        1995, // Super Mario Bros. 3 (NES)
        335, // Legend of Zelda, The: A Link to the Past (SNES)
        446, // Donkey Kong Country 2: Diddy's Kong Quest (SNES)
        724, // Pokemon Red | Pokemon Blue (GB/C)
        5371, // Legend of Zelda, The: Link's Awakening DX (GB/C)
        10078, // Mario Kart 64 (N64)
        10210, // Banjo-Kazooie (N64)
        10434, // Crash Bandicoot (PSX)
        11242, // Final Fantasy VII (PSX)
        11191, // Pitfall! (Atari 2600)
        2831, // Metal Gear Solid 3: Subsistence (PS2)
        2721, // Dragon Quest VIII: Journey of the Cursed King (PS2)
    ];
?>

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
            <a href="{{ route('game.show', $randomGameIds[array_rand($randomGameIds)]) }}">Which of these achievements do you think you can get?</a>
        </p>
    </div>
</x-section>
