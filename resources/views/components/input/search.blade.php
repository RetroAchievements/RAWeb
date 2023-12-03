<?php
$id ??= 'search';
$autoFocus ??= false;
$loading ??= false;
?>
<div class="input-group {{ ($sm ?? false) ? 'input-group-sm' : '' }}">
    @if(!isset($showIcon) || $showIcon)
        <span class="input-group-prepend">
            <label for="{{ $id }}" class="input-group-text bg-transparent border-0">
                @if($loading)
                    <span wire:loading wire:target="search"><x-loader size="xs" /></span>
                    <x-fas-search wire:loading.remove wire:target="search" />
                @else
                    <x-fas-search />
                @endif
                <span class="sr-only">Search</span>
            </label>
        </span>
    @endif
    <input wire:model.live.debounce.500ms="search"
           name="search"
           {{ $autoFocus ? 'autofocus' : '' }}
           type="search"
           id="{{ $id }}"
           class="form-control border-0"
           placeholder="{{ __('Search') }}&hellip;">
    @if($showButton ?? false)
        <span class="input-group-append">
            <button class="btn btn-link">{{ __('Search') }}</button>
        </span>
    @endif
</div>
{{--<script nonce="{{ csp_nonce() }}">
    //document.getElementById('search').focus()
</script>--}}
