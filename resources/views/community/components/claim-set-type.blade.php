@php
use LegacyApp\Community\Enums\ClaimSetType;
@endphp

<div class="flex items-center gap-1 text-xs">
@if ($claimSetType == ClaimSetType::NewSet)
    <x-pixelarticons-card-plus class="w-4 h-4 text-green-500" />
@else
    <x-pixelarticons-edit class="w-4 h-4 text-cyan-400" />
@endif
    <span>{{ ClaimSetType::toString($claimSetType) }}</span>
</div>
