<?php

use App\Community\Enums\RequestStatus;

$any = RequestStatus::Any;
?>

@props([
    'selectedRequestStatus' => $any,
    'value' => $any,
])

<label class="transition lg:active:scale-95 cursor-pointer flex items-center gap-x-1 text-xs">
    <input type="radio" class="cursor-pointer" name="request-status" value="{{ $value }}" {{ $selectedRequestStatus == $value ? 'checked' : '' }} @change="handleRequestStatusChanged">
    {{ $slot }}
</label>
