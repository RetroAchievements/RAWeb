@can($ability ?? 'delete', $model)
    <?php
    ?>
    <x-base.button.form class="btn btn-danger border-0"
                   :action="$link ?? route(($resource ?? resource_type($model)) . '.destroy', $model)"
                   method="delete"
                   :title="empty((string)$slot) ? __('Delete :resource', ['resource' => __res($resource ?? resource_type($model), 1)]) : ''"
    >
        {{ svg('fas-' . ($icon ?? 'trash'), 'icon') }}
        {{ $slot }}
    </x-base.button.form>
@endcan
