<?php
$action ??= 'refresh';
?>
<button
    wire:click="render"
    class="btn"
    data-toggle="tooltip" title="{{ __('Refresh') }}"
>
    <x-fas-sync-alt wire:loading.class="icon-spin" />
    {{ $slot }}
</button>
