<?php
$id = uniqid();
?>
<li class="nav-item dropdown {{ ($active ?? false) ? 'active' : '' }}">
    <a
        href="#"
        class="nav-link {{--dropdown-toggle--}} {{ $triggerClass ?? '' }}"
        id="navbarDropdownButton{{ $id }}"
        data-toggle="dropdown"
        role="button"
        aria-haspopup="true"
        aria-expanded="false"
        title="{{ $title ?? '' }}"
        data-toggle="tooltip"
    >
        {{ $trigger }}
    </a>
    <div class="dropdown-menu {{ $dropdownClass ?? '' }}" aria-labelledby="navbarDropdownButton{{ $id }}">
        {{ $slot }}
    </div>
</li>
