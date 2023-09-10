@props([
    'totalConsoleCount' => 0,
])

<script>
/**
 * @param {boolean} isSettingVisible
 */
function toggleRowsVisibility(isSettingVisible) {
    const hiddenRowsContainerEl = document.querySelector('#hidden-progression-status-rows');
    const seeRowsButtonEl = document.querySelector('#see-all-consoles-button');
    const hideRowsButtonEl = document.querySelector('#hide-consoles-button');

    if (isSettingVisible) {
        seeRowsButtonEl.classList.add('hidden');
        hiddenRowsContainerEl.classList.remove('hidden', 'animate-collapse-open');
        hiddenRowsContainerEl.classList.add('flex', 'animate-collapse-open');
        hideRowsButtonEl.classList.remove('hidden');
    } else {
        seeRowsButtonEl.classList.remove('hidden');
        hideRowsButtonEl.classList.add('hidden');
        hiddenRowsContainerEl.classList.remove('flex', 'animate-collapse-open');
        hiddenRowsContainerEl.classList.add('hidden');
    }
}
</script>

<button id="see-all-consoles-button" class="btn" onclick="toggleRowsVisibility(true)">
    See all {{ $totalConsoleCount }} consoles
</button>

<div id="hidden-progression-status-rows" class="hidden flex-col gap-y-1.5 transition-all">
    {{ $slot }}
</div>

<button id="hide-consoles-button" class="mt-1.5 hidden btn" onclick="toggleRowsVisibility(false)">
    See less
</button>
