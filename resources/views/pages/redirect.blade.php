<?php
use function Laravel\Folio\{name};

name('redirect');
?>

<x-app-layout :page-title="__('Redirect')" :noindex="true">
    <div class="grid gap-y-6">
        <div class="rounded bg-gradient-to-b from-amber-400 to-yellow-700 w-full p-2">
            <div class="flex flex-col gap-y-2 items-center md:flex-row md:gap-x-6">
                <img src="/assets/images/cheevo/popcorn.webp" alt="cheevo eating popcorn" class="w-24 h-24">

                <div class="text-center md:text-left md:flex md:flex-col md:gap-y-1">
                    <p class="text-white md:text-base">Heads up!</p>
                    <h1 class="text-base border-0 mb-0 text-white md:text-lg md:font-bold">You are leaving RetroAchievements.</h1>
                </div>
            </div>
        </div>

        <div class="bg-embed rounded border border-embed-highlight p-4 md:text-center">
            <div class="flex flex-col gap-y-4">
                <p>
                    <span class="font-bold">{{ $url }}</span> is not part of RetroAchievements.
                    We don't know what you might see there.
                </p>

                <div class="w-full flex justify-center">
                    <a href="{{ $url }}" rel="noreferrer" class="btn">Continue to external site</a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
