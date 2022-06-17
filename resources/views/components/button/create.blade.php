<?php
$abilityArguments = resource_class($resource);
if ($model ?? false) {
    $abilityArguments = [resource_class($resource), $model];
}
?>
@can('create', $abilityArguments)
    <a class="btn btn-link border-0 whitespace-nowrap"
       href="{{ $link ?? route($resource.'.create', $model ?? null) }}"
       data-toggle="tooltip" title="{{ empty((string)$slot) ? __('Add :resource', ['resource' => __res($resource, 1)]) : '' }}">
        {{ svg('fas-' . ($icon ?? 'plus'), 'icon') }}
        {{ $slot }}
    </a>
@endcan
