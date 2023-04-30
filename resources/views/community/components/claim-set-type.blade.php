@php
use LegacyApp\Community\Enums\ClaimSetType;
@endphp

<div class="flex items-center gap-1 text-xs">
@if ($claimSetType == ClaimSetType::NewSet)
    <x-pixelarticons-check class="w-5 h-5 text-lime-400" />
@else
    <x-pixelarticons-check-double class="w-5 h-5 text-cyan-300" />
@endif
    <span>{{ ClaimSetType::toString($claimSetType) }}</span>
</div>
