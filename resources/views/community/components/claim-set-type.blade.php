@php
use LegacyApp\Community\Enums\ClaimSetType;
@endphp

<div class="flex items-center gap-1 text-xs">
@if ($claimSetType == ClaimSetType::NewSet)
    <x-pixelarticons-check class="w-5 h-5 text-lime-500" />
@else
    <x-pixelarticons-check-double class="w-5 h-5 text-amber-600" />
@endif
    <span>{{ ClaimSetType::toString($claimSetType) }}</span>
</div>
