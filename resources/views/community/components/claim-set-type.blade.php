<?php

use LegacyApp\Community\Enums\ClaimSetType;

[$claimSetTypeStr, $claimSetTypeIcon] = ['', ''];
if ($claim['SetType'] === ClaimSetType::NewSet) {
    $claimSetTypeStr = ClaimSetType::toString(ClaimSetType::NewSet);
} else {
    $claimSetTypeStr = ClaimSetType::toString(ClaimSetType::Revision);
}
?>

<div class="flex items-center gap-1 text-xs">
    <x-pixelarticons-lock-open class="w-5 h-5" />
    <span>{{ $claimSetTypeStr }}</span>
</div>
