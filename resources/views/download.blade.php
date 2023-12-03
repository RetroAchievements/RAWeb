<x-app-layout :page-title="__('Download a client')">
    <h2 class="mb-6">Emulators supporting RetroAchievements</h2>

    @foreach ($emulators as $emulator)
        <section id="{{ mb_strtolower($emulator['handle'] ?? '') }}">
            <h2 class="longheader">
                <a href="#{{ mb_strtolower($emulator['handle'] ?? '') }}">{{ $emulator['handle'] ?? '' }}</a>
                @if ($emulator['handle'] != $emulator['name'])
                    <small>({{ $emulator['name'] ?? '' }})</small>
                @endif
            </h2>
            <div class="flex flex-col lg:flex-row justify-between items-start mb-6">
                <div class="mb-3 w-full">
                    @isset($emulator['description'])
                        <div class="mb-2">{!! $emulator['description'] !!}</div>
                    @endisset

                    @isset($emulator['systems'])
                        <div class="flex-1 mb-3">
                            <b>Supported Systems:</b><br>
                            <ul style="column-count: 3">
                                @foreach ($emulator['systems'] as $system)
                                    <li>- {{ $system }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endisset
                </div>
                <div>
                    @if(array_key_exists('download_url', $emulator) && !empty($emulator['download_url']))
                        <p class="embedded mb-2 text-right whitespace-nowrap">
                            <x-link link="{{ $emulator['download_url'] }}">Download</x-link>
                        </p>
                    @endif

                    @if(array_key_exists('latest_version_url_x64', $emulator) && !empty($emulator['latest_version_url_x64']))
                        <p class="embedded mb-2 text-right whitespace-nowrap">
                            <a href="{{ config('app.url') . '/' . $emulator['latest_version_url_x64'] }}">
                                Download v{{ $emulator['latest_version'] }} x64<br>
                                <small>Windows</small>
                            </a>
                        </p>
                    @endif

                    @if(array_key_exists('latest_version_url', $emulator) && !empty($emulator['latest_version_url']))
                        <p class="embedded mb-2 text-right whitespace-nowrap">
                            <a href="{{ config('app.url') . '/' . $emulator['latest_version_url'] }}">
                                Download v{{ $emulator['latest_version'] }} x86<br>
                                <small>Windows</small>
                            </a>
                        </p>
                    @endif

                    @if(array_key_exists('link', $emulator) && !empty($emulator['link']))
                        <p class="embedded mb-2 text-right whitespace-nowrap">
                            <x-link link="{{ $emulator['link'] }}">Documentation</x-link>
                        </p>
                    @endif

                    @if(array_key_exists('source', $emulator) && !empty($emulator['source']))
                        <p class="embedded mb-2 text-right whitespace-nowrap">
                            <x-link link="{{ $emulator['source'] }}">Source Code</x-link>
                        </p>
                    @endif
                </div>
            </div>
        </section>
    @endforeach

    @include('content.legal')
</x-app-layout>
