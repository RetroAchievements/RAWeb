<?php

use LegacyApp\Community\Enums\ClaimSetType;
?>

<div class="flex items-center gap-1 text-xs">
@if ($claim['SetType'] == ClaimSetType::NewSet)    
    <x-pixelarticons-lock-open class="w-5 h-5" />
@else
    <x-pixelarticons-lock-open class="w-5 h-5" />
@endif
    <span>{{ ClaimSetType::toString($claim['SetType']) }}</span>
</div>
