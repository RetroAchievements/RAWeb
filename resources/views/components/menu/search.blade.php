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
<form class="flex searchbox-top" action="/searchresults.php">
    <input name="s" type="text" class="flex-1 searchboxinput" value="{!! $searchQuery !!}" placeholder="{{ __('Search') }}">
    <button class="nav-link" title="Search"><x-fas-search class="w-3 h-3"/></button>
</form>
