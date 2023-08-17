<?php
$id = uniqid();
?>
<div class="dropdown {{ $class ?? '' }} {{ ($active ?? false) ? 'active' : '' }}">
    <button
        class="{{ $triggerClass ?? '' }}"
        id="dropdownTrigger{{ $id }}"
        role="button"
        aria-haspopup="true"
        aria-expanded="false"
    >
        {{ $trigger }}
    </button>
    <div class="dropdown-menu {{ $dropdownClass ?? '' }}" aria-labelledby="dropdownTrigger{{ $id }}">
        {{ $slot }}
    </div>
</div>
