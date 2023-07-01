<?php
use App\Community\Enums\RequestStatus;

$any = RequestStatus::Any;
$claimed = RequestStatus::Claimed;
$unclaimed = RequestStatus::Unclaimed;
?>

<label class="text-xs font-bold sm:-mb-6">Request status</label>
<div class="space-x-4 flex" id="filter-by-request-status">
    <x-request-list.meta-request-status-filter-radio value="{{ $any }}" :selectedRequestStatus="$selectedRequestStatus">
        Any
    </x-request-list.meta-request-status-filter-radio>

    <x-request-list.meta-request-status-filter-radio value="{{ $claimed }}" :selectedRequestStatus="$selectedRequestStatus">
        Claimed
    </x-request-list.meta-request-status-filter-radio>

    <x-request-list.meta-request-status-filter-radio value="{{ $unclaimed }}" :selectedRequestStatus="$selectedRequestStatus">
        Unclaimed
    </x-request-list.meta-request-status-filter-radio>
</div>
