<?php
$type ??= 'integer';
$fractionDigits ??= 1;
?>
@if($value && $value > 0)
    <div class="mr-3">
        <div class="uppercase text-secondary">
            <small>{{ $label }}</small>
        </div>
        @if($type=== 'integer')
            <x-number :number="$value" />
        @endif
        @if($type=== 'percent')
            <x-number :number="$value" :fractionDigits="$fractionDigits" percent />
        @endif
    </div>
@endif
