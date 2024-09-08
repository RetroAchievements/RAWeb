<?php

$emulators = getActiveEmulatorReleases();

authenticateFromCookie($user, $permissions, $userDetails);
?>
<x-app-layout
    pageTitle="Download a supported emulator"
    pageDescription="Get started with RetroAchievements by downloading an emulator with built-in RetroAchievements support."
>
    <h2 class="mb-6">Download a supported emulator</h2>

    @foreach ($emulators as $emulator)
        <h2 class="longheader" id="<?= mb_strtolower($emulator['name'] ?? null) ?>">
            <a href="#<?= mb_strtolower($emulator['name'] ?? null) ?>"><?= $emulator['name'] ?? null ?></a>
            @if ($emulator['original_name'] != $emulator['name'])
                <small>(<?= $emulator['original_name'] ?? null ?>)</small>
            @endif
        </h2>
        <div class="flex flex-col lg:flex-row justify-between items-start mb-6">
            <div class="mb-3 w-full">
                @if ($emulator['description'] ?? false)
                    <div class="mb-2"><?= nl2br($emulator['description']) ?></div>
                @endif
                <div class="flex-1 mb-3">
                    @if (!empty($emulator['systems']))
                        <b>Supported Systems:</b><br>
                        <ul style="column-count: 3">
                        @foreach ($emulator['systems'] as $system)
                            <li>- {{ $system }}</li>
                        @endforeach
                        </ul>
                    @endif
                </div>
            </div>
            <div>
                @if ($emulator['download_url'] ?? false)
                    <p class="embedded mb-2 text-right whitespace-nowrap">
                        <x-link
                            href="{{ $emulator['download_url'] }}"
                            target="_blank"
                            class="plausible-event-name=Download+Link+Click plausible-event-emulator={{ $emulator['name'] }}"
                        >
                            Download
                        </x-link>
                    </p>
                @endif
                @if ($emulator['latest_version_url_x64'] ?? false)
                    <p class="embedded mb-2 text-right whitespace-nowrap">
                        <a
                            href="<?= config('app.url') . '/' . $emulator['latest_version_url_x64'] ?>"
                            target="_blank"
                            class="plausible-event-name=Download+Link+Click plausible-event-emulator-{{ $emulator['name'] }}"
                        >
                            Download v<?= $emulator['latest_version'] ?> x64<br>
                            <small>Windows</small>
                        </a>
                    </p>
                @endif
                @if ($emulator['latest_version_url'] ?? false)
                    <p class="embedded mb-2 text-right whitespace-nowrap">
                        <a
                            href="<?= config('app.url') . '/' . $emulator['latest_version_url'] ?>"
                            target="_blank"
                            class="plausible-event-name=Download+Link+Click plausible-event-emulator-{{ $emulator['name'] }}"
                        >
                            Download v<?= $emulator['latest_version'] ?> x86<br>
                            <small>Windows</small>
                        </a>
                    </p>
                @endif
                @if ($emulator['documentation_url'] ?? false)
                    <p class="embedded mb-2 text-right whitespace-nowrap">
                        <x-link
                            href="{{ $emulator['documentation_url'] }}"
                            target="_blank"
                            class="plausible-event-name=Documentation+Link+Click plausible-event-emulator-{{ $emulator['name'] }}"
                        >
                            Documentation
                        </x-link>
                    </p>
                @endif
                @if ($emulator['source_url'] ?? false)
                    <p class="embedded mb-2 text-right whitespace-nowrap">
                        <x-link
                            href="{{ $emulator['source_url'] }}"
                            target="_blank"
                            class="plausible-event-name=Source+Link+Click plausible-event-emulator-{{ $emulator['name'] }}"
                        >
                            Source Code
                        </x-link>
                    </p>
                @endif
            </div>
        </div>
    @endforeach

    <x-content.legal />
</x-app-layout>
