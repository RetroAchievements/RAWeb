{{--@if(!Route::is('search'))
    <div class="lg:hidden">
        <x-nav-item :link="route('search')">
            <x-fas-search/>
        </x-nav-item>
    </div>
    <div class="hidden lg:block">
        <livewire:supersearch dropdown/>
    </div>
@endif--}}
<?php
$searchQuery = null;
if ($_SERVER['SCRIPT_NAME'] === '/searchresults.php') {
    $searchQuery = attributeEscape(request()->query('s'));
}
?>
<div
    x-data="navbarSearchComponent"
    class="searchbox-container"
    x-init="init($refs.searchForm,$refs.searchListbox)"
    @click.outside="showSearchResults = false"
>
    <form class="flex searchbox-top" x-ref="searchForm" action="/searchresults.php">
        <input
            name="s"
            type="text"
            role="combobox"
            class="flex-1 searchboxinput"
            placeholder=" {{ __('Search') }}"
            x-model="searchText"
            @keydown.up="handleUp"
            @keydown.down="handleDown"
            @keydown.enter="$event.preventDefault()"
            @keyup.enter="handleEnter"
            @keyup.escape="handleEscape"
            @keyup.debounce="handleKeyUp($event)"
            @blur="showSearchResults = false"
            aria-autocomplete="list"
            aria-controls="search-listbox"
            :aria-expanded="showSearchResults"
            :aria-activedescendant="activeDescendentId"
        >
        <button class="nav-link" title="Search">
            <x-fas-search />
        </button>
    </form>

    <ul
        id="search-listbox"
        role="listbox"
        aria-label="Search"
        class="p-0.5 w-fit absolute top-0 left-0 rounded-lg bg-yellow-100 z-20"
        x-ref="searchListbox"
        x-show="showSearchResults"
    >
        <template x-for="(result, i) in results">
            <li
                :id="getOptionId(result)"
                role="option"
                tabindex="-1"
                class="text-sm cursor-pointer"
                :class="selectedIndex === i ? 'rounded-lg bg-amber-200 border-2 border-yellow-700 py-[1px] px-[4px]' : 'py-[3px] px-[6px] hover:rounded-lg hover:bg-amber-200 hover:border-2 hover:border-yellow-700 hover:py-[1px] hover:px-[4px]'"
                :aria-selected="selectedIndex === i"
                @click="handleClickSearchResult(result.label, result.mylink)"
                @mouseDown="$event.preventDefault()"
            >
                <a
                   class="text-black hover:text-black flex"
                   :href="result.mylink"
                   x-text="result.label"
                ></a>
            </li>
        </template>
    </ul>
</div>
