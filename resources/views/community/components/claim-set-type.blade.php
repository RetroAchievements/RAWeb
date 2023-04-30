<?php

use LegacyApp\Community\Enums\ClaimSetType;
?>

<div class="flex items-center gap-1 text-xs">
@if ($claimSetType == ClaimSetType::NewSet)
    <x-pixelarticons-check class="w-5 h-5 text-lime-500" />
@else
    <x-pixelarticons-check-double class="w-5 h-5 text-orange-500" />
@endif
    <span>{{ ClaimSetType::toString($claimSetType) }}</span>
</div>
