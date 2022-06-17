<?php
/**
 * used for mobile presentation where dropdowns won't work in horizontally scrollable navbars
 */
$mobile ??= false;
?>
{{--<ul class="navbar-nav">
    <x-menu.play :mobile="$mobile" />
    <x-menu.create :mobile="$mobile" />
    <x-menu.community :mobile="$mobile" />
    @if(!$mobile)
        <x-nav-item :link="route('download.index')">{{ __('Downloads') }}</x-nav-item>
        <x-nav-item :link="route('tool.index')">{{ __('Tools') }}</x-nav-item>
    @endif
</ul>--}}
<div class="lg:flex gap-4 justify-between items-center" id="innermenu">
    <?php RenderToolbar() ?>
</div>
