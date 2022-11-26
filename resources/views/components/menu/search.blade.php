{{--@if(!Route::is('search'))
    <ul class="navbar-nav lg:hidden">
        <x-nav-item :link="route('search')">
            <x-fas-search/>
        </x-nav-item>
    </ul>
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
<form class="flex searchbox-top" action="/searchresults.php">
    <input name="s" type="text" class="flex-1 searchboxinput hidden lg:block" value="{{ $searchQuery }}" placeholder="{{ __('Search') }}">
    <button class="nav-link" title="Search"><x-pixelarticons-search class="w-5 h-5"/></button>
</form>
