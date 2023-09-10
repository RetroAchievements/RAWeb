@props([
    'gameId' => 0,
])

<script>
function beginResetProgress() {
    const confirmMessage = "DANGER: This will remove all your unlocked achievements for the game! Are you sure you want to reset your progress?";

    if (confirm(confirmMessage)) {
        window.showStatusMessage("Resetting progress...");

        $.post('/request/user/reset-achievements.php', {
            game: {{ $gameId }}
        }).done(() => {
            window.location.reload();
        });
    }
}
</script>

<div x-data="{ isMenuOpen: false }" class="relative inline">
    <button 
        aria-label="Open progress secondary actions menu"
        aria-expanded="false"
        x-bind:aria-expanded="isMenuOpen.toString()"
        class="text-link rounded border border-embed-highlight bg-embed p-1.5 hover:text-link-hover hover:border-menu-link hover:bg-embed-highlight transition-transform md:active:scale-95" 
        @click="isMenuOpen = true"
    >
        <div class="w-3 h-3">
            <svg xmlns:xlink="http://www.w3.org/1999/xlink" stroke="currentColor" fill="none" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg">
                <path stroke="none" d="M0 0h24v24H0z" fill="none" stroke-width="2px"></path>
                <path d="M4 6l16 0" fill="none" stroke-width="2px"></path>
                <path d="M4 12l16 0" fill="none" stroke-width="2px"></path>
                <path d="M4 18l16 0" fill="none" stroke-width="2px"></path>
            </svg>
        </div>
    </button>

    <div
        x-cloak
        x-show="isMenuOpen"
        @click.away="isMenuOpen = false"
        @keydown.escape.window="isMenuOpen = false"
        role="menu"
        x-transition:enter="transition ease-out duration-200 transform"
        x-transition:enter-start="opacity-0 -translate-y-2 translate-x-1"
        x-transition:enter-end="opacity-100 translate-y-0 translate-x-0"
        x-transition:leave="transition ease-in duration-150 transform"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="absolute top-5 right-0 w-56"
    >
        <div class="top-0 py-2 border border-embed-highlight bg-box-bg rounded shadow-lg focus:outline-none focus-visible:ring-2">
            <button
                @click="isMenuOpen = false; beginResetProgress();"
                role="menuitem"
                class="w-full text-red-600 flex justify-start leading-none items-center px-4 py-2 hover:bg-embed light:hover:text-red-500 whitespace-nowrap"
            >
                Reset all progress
            </button>
        </div>
    </div>
</div>