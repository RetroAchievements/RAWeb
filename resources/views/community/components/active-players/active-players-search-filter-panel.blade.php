@props([
    'initialSearch' => null,
])

<div
    class="flex w-full items-center gap-x-2 bg-embed border border-embed-highlight rounded {{ $initialSearch ? 'flex py-2.5 px-2' : '' }}"
    :class="{'py-2.5 px-2 !rounded-tr-none': isMenuOpen}"
    x-show="isMenuOpen"
    x-transition:enter="transition-all ease-out duration-200" 
    x-transition:enter-start="opacity-0 h-0 overflow-y-hidden" 
    x-transition:enter-end="opacity-100 h-[51px]"
    x-transition:leave="transition-all ease-in-out duration-200" 
    x-transition:leave-start="opacity-100 h-[51px] opacity-100"
    x-transition:leave-end="opacity-0 h-0 opacity-0"
    @if (!$initialSearch) x-cloak @endif
>
    <input
        class="w-full"
        :class="{'hidden': !isMenuOpen}"
        placeholder="Search by player, game, console, or Rich Presence..."
        @if ($initialSearch) value="{{ $initialSearch }}" @endif
        x-model.debounce.500ms="searchInput"
    >

    <label class="flex items-center gap-x-1 select-none cursor-pointer text-xs whitespace-nowrap" :class="{'hidden': !isMenuOpen}">
        <input
            type="checkbox"
            autocomplete="off"
            class="cursor-pointer"
            @if ($initialSearch) checked @endif
            @change="toggleSavedSearch"
            x-model="isRememberingSearch"
        >
        Remember my search
    </label>
</div>
