@can('update', $model)
    <a class="btn"
       href="{{ $link ?? route(resource_type($model).'.edit', $model) }}"
       data-toggle="tooltip" title="{{ empty((string)$slot) ? __('Edit :resource', ['resource' => __res(resource_type($model), 1)]) : '' }}">
        {{ svg('fas-' . ($icon ?? 'pencil-alt'), 'icon') }}
        {{ $slot }}
    </a>
@endcan
