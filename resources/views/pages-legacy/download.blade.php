<?php

use App\Models\Emulator;

$emulators = Emulator::active()
    ->orderBy('name')
    ->with(['latestRelease'])
    ->get();

authenticateFromCookie($user, $permissions, $userDetails);
?>
<x-app-layout
    pageTitle="Download a supported emulator"
    pageDescription="Get started with RetroAchievements by downloading an emulator with built-in RetroAchievements support."
>
    <h2 class="mb-6">Download a supported emulator</h2>

    @foreach ($emulators as $emulator)
        @php
            $systems = $emulator->systems()->active()->orderBy('Name')->pluck('Name')->toArray();
            if (empty($systems)) {
                continue;
            }
        @endphp
        <h2 class="longheader" id="<?= mb_strtolower($emulator['name'] ?? null) ?>">
            <a href="#<?= mb_strtolower($emulator->name) ?>">{{ $emulator->name }}</a>
            @if ($emulator->original_name !== null && $emulator->original_name !== $emulator->name)
                <small>({{ $emulator->original_name }})</small>
            @endif
        </h2>
        <div class="flex flex-col lg:flex-row justify-between items-start mb-6">
            <div class="mb-3 w-full">
                @if (!empty($emulator->description))
                    <div class="mb-2">{{ $emulator->description }}</div>
                @endif
                <div class="flex-1 mb-3">
                    <b>Supported Systems:</b><br>
                    <ul style="column-count: 3">
                    @foreach ($systems as $system)
                        <li>- {{ $system }}</li>
                    @endforeach
                    </ul>
                </div>
            </div>
            <div>
                @if (!empty($emulator->download_url) || !empty($emulator->download_x64_url))
                    @if (str_starts_with($emulator->download_url, 'bin/') || str_starts_with($emulator->download_x64_url, 'bin/'))
                        @if (!empty($emulator->download_x64_url))
                            <p class="embedded mb-2 text-right whitespace-nowrap">
                                <a
                                    href="<?= config('app.url') . '/' . $emulator->download_x64_url ?>"
                                    target="_blank"
                                    class="plausible-event-name=Download+Link+Click plausible-event-emulator-{{ $emulator->name }}"
                                >
                                    Download v{{ $emulator->latestRelease->version }} x64<br>
                                    <small>Windows</small>
                                </a>
                            </p>
                        @endif
                        @if (!empty($emulator->download_url))
                            <p class="embedded mb-2 text-right whitespace-nowrap">
                                <a
                                    href="<?= config('app.url') . '/' . $emulator->download_url ?>"
                                    target="_blank"
                                    class="plausible-event-name=Download+Link+Click plausible-event-emulator-{{ $emulator->name }}"
                                >
                                    Download v{{ $emulator->latestRelease->version }} x86<br>
                                    <small>Windows</small>
                                </a>
                            </p>
                        @endif
                    @else
                        <p class="embedded mb-2 text-right whitespace-nowrap">
                            <x-link
                                href="{{ $emulator->download_url }}"
                                target="_blank"
                                class="plausible-event-name=Download+Link+Click plausible-event-emulator={{ $emulator->name }}"
                            >
                                Download
                            </x-link>
                        </p>
                    @endif
                @endif
                @if (!empty($emulator->documentation_url))
                    <p class="embedded mb-2 text-right whitespace-nowrap">
                        <x-link
                            href="{{ $emulator->documentation_url }}"
                            target="_blank"
                            class="plausible-event-name=Documentation+Link+Click plausible-event-emulator-{{ $emulator->name }}"
                        >
                            Documentation
                        </x-link>
                    </p>
                @endif
                @if (!empty($emulator->source_url))
                    <p class="embedded mb-2 text-right whitespace-nowrap">
                        <x-link
                            href="{{ $emulator->source_url }}"
                            target="_blank"
                            class="plausible-event-name=Source+Link+Click plausible-event-emulator-{{ $emulator->name }}"
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
