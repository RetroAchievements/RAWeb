<div 
    class="component" 
    wire:poll.5.minutes="updateActivePlayers"
    x-data="activePlayers({{ json_encode($activePlayers) }})"
>
    <h3>Active Players</h3>

    <div class="flex justify-between mb-2">
        <x-active-players-meta-bar 
            :active-players-count="count($activePlayers)"
            :has-error="$hasError"
        />
    </div>

    <template x-if="isSearchMenuOpen">
        <x-active-players-filtering />
    </template>

    <div class="min-h-[54px] h-80 max-h-80 overflow-y-auto mb-2">
        @if ($hasError)
            <div class="flex w-full h-full justify-center items-center">
                <p>An error has occurred while loading players.</p>
            </div>
        @else
            <x-active-players-list :activePlayers="$activePlayers" />
        @endif
    </div>

    <p
        class="w-full flex justify-end text-2xs"
        x-data="{ updated: new Date() }"
        x-text="buildLastUpdatedTime()"
        x-on:poll.window="updated = new Date()"
    >
        Last updated at X:XX XX
    </p>
</div>