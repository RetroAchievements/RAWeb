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
     @click.outside="showSearchResults = false">
    <form class="flex searchbox-top" x-ref="searchForm" action="/searchresults.php">
        <input
               name="s"
               type="text"
               role="combobox"
               class="flex-1 searchboxinput"
               placeholder=" {{ __('Search') }}"
               x-model="searchText"
               @keyup="handleKeyUp($event)"
               @keydown="handleKeyDown"
               @blur="showSearchResults = false"
               aria-autocomplete="list"
               aria-controls="search-listbox"
               :aria-expanded="showSearchResults"
               :aria-activedescendant="activeDescendentId">
        <button class="nav-link" title="Search">
            <x-fas-search />
        </button>
    </form>
    <ul
        id="search-listbox"
        role="listbox"
        aria-label="Search"
        class="p-0.5 w-fit absolute top-0 left-0 rounded-lg bg-yellow-100"
        x-ref="searchListbox"
        x-show="showSearchResults">
        <template x-for="(result, i) in results">
            <li
                :id="result.mylink.slice(1).replace('/','-')"
                role="option"
                tabindex="-1"
                class="text-sm cursor-pointer hover:rounded-lg hover:bg-amber-200 hover:border-2 hover:border-yellow-700"
                :class="selectedIndex - 2 === i ? 'listbox-item--hover' : ''"
                :aria-selected="selectedIndex - 2 === i"
                @click="handleClickSearchResult(result.label)"
                @mouseDown="$event.preventDefault()">
                <a
                   class="text-black hover:text-black flex"
                   :href="result.mylink"
                   x-text="result.label"></a>
            </li>
        </template>
    </ul>
</div>