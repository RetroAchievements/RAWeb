<?php

use Illuminate\Support\Carbon;
?>

@props([
    'gameId' => 1,
    'hashEntity' => null, // GameHash
    'myUsername' => '',
])

<div class="border border-embed-highlight bg-embed rounded-lg overflow-hidden">
    <div class="flex w-full justify-between px-4 py-2 bg-stone-950 light:bg-stone-100 border-b border-embed-highlight items-center">
        <p class="font-bold">
            {{ $hashEntity->name }}
        </p>

        <div class="flex gap-x-4 items-center">
            <p class="font-mono text-neutral-200 light:text-neutral-700">
                {{ $hashEntity->md5 }}
            </p>

            <button
                type="button"
                class="btn btn-danger transition-transform lg:active:scale-95"
                onclick="unlinkHash('{{ $hashEntity->md5 }}')"
            >
                Unlink
            </button>
        </div>
    </div>

    <div class="p-4">
        @if ($hashEntity->user)
            <p class="mb-4">
                Linked by {!! userAvatar($hashEntity->user, icon: false) !!}
                @if ($hashEntity->created_at)
                    on {{ Carbon::parse($hashEntity->created_at)->format('F j Y, g:ia') }}
                @endif
            </p>
        @elseif ($hashEntity->created_at)
            <p class="mb-4">
                Linked on {{ Carbon::parse($hashEntity->created_at)->format('F j Y, g:ia') }}
            </p>
        @endif

        <form onsubmit="updateHashDetails(event, '{{ $hashEntity->md5 }}')">
            <div class="grid lg:grid-cols-2 gap-4 mb-4">
                <div>
                    <label
                        for="{{ 'HASH_' . $hashEntity->md5 . '_Name' }}"
                        class="text-2xs font-semibold"
                    >
                        File Name
                    </label>

                    <input
                        id="{{ 'HASH_' . $hashEntity->md5 . '_Name' }}"
                        class="w-full"
                        value="{{ $hashEntity->name }}"
                    >
                </div>

                <div>
                    <label
                        for="{{ 'HASH_' . $hashEntity->md5 . '_Labels' }}"
                        class="text-2xs font-semibold"
                    >
                        Labels
                    </label>

                    <input
                        id="{{ 'HASH_' . $hashEntity->md5 . '_Labels' }}"
                        class="w-full"
                        value="{{ $hashEntity->labels }}"
                    >
                </div>

                <div>
                    <label
                        for="{{ 'HASH_' . $hashEntity->md5 . '_PatchURL' }}"
                        class="text-2xs font-semibold cursor-help flex items-center gap-x-0.5"
                        title="Optional. This MUST be a URL to a .zip or .7z file in the RAPatches GitHub repo, eg: https://github.com/RetroAchievements/RAPatches/raw/main/NES/Subset/5136-CastlevaniaIIBonus.zip"
                    >
                        RAPatches URL
                        <x-fas-info-circle class="text-sm" />
                    </label>

                    <input
                        id="{{ 'HASH_' . $hashEntity->md5 . '_PatchURL' }}"
                        class="w-full"
                        value="{{ $hashEntity->patch_url }}"
                        placeholder="https://github.com/RetroAchievements/RAPatches/raw/main/NES/Subset/5136-CastlevaniaIIBonus.zip"
                    >
                </div>

                <div>
                    <div class="flex w-full justify-between">
                        <label
                            for="{{ 'HASH_' . $hashEntity->md5 . '_SourceURL' }}"
                            class="text-2xs font-semibold cursor-help flex items-center gap-x-0.5"
                            title="Optional. Link to a specific No Intro, Redump, RHDN, SMWCentral, itch.io, etc. page."
                        >
                            Resource Page URL
                            <x-fas-info-circle class="text-sm" />
                        </label>

                        <p class="smalltext">Do not link to a commercially-sold ROM.</p>
                    </div>

                    <input
                        id="{{ 'HASH_' . $hashEntity->md5 . '_SourceURL' }}"
                        class="w-full"
                        value="{{ $hashEntity->source }}"
                        placeholder="http://redump.org/disc/23548/"
                    >
                </div>
            </div>

            <div class="w-full flex justify-end">
                <button class="btn transition-transform lg:active:scale-95">Update</button>
            </div>
        </form>
    </div>
</div>
