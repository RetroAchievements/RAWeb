<?php
use LegacyApp\Community\Enums\RequestStatus;

$any = RequestStatus::ANY;
?>

@props([
    'selectedRequestStatus' => $any,
    'value' => $any,
])

<label class="cursor-pointer flex items-center gap-x-1 text-xs">
    <input type="radio" name="request-status" value="{{ $value }}" {{ $selectedRequestStatus->value == $value ? 'checked' : '' }} @change="handleRequestStatusChanged">
    {{ $slot }}
</label>