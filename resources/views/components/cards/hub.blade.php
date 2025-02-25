@props([
    'hubId' => 0,
])

@use('App\Models\GameSet')
@use('App\Models\System')
@use('App\Platform\Enums\GameSetType')

@php
$hub = GameSet::find($hubId);
@endphp

<x-card.container imgSrc="{{ media_asset($hub->image_asset_path) }}" imgKind="hub">
    <div class="relative h-full text-2xs">
        {{-- Hub Name --}}
        <p class="font-bold -mt-1 line-clamp-2 {{ mb_strlen($hub->title) > 24 ? 'text-sm leading-5 mb-1' : 'text-lg leading-6 mb-0.5' }}">
            <x-game-title :rawTitle="$hub->title" />
        </p>

        {{-- Icon and "Hubs" label --}}
        <div class="flex items-center gap-x-1">
            <img src="{{ getSystemIconUrl(System::Hubs) }}" width="18" height="18" alt="hub icon">
            <span class="block text-sm tracking-tighter">Hubs</span>
        </div>

        <div class="mb-2"></div>

        <x-card.info-row label="Games">
            {{ localized_number($hub->games->count()) }}
        </x-card.info-row>

        <x-card.info-row label="Links">
            {{ localized_number($hub->children->count()) }}
        </x-card.info-row>
    </div>
</x-card.container>
