<a class="btn whitespace-nowrap" href="{{ $link ?? $model->canonicalUrl }}"
   data-toggle="tooltip" title="{{ empty((string)$slot) ? __('Cancel') : '' }}">
    {{ svg('fas-' . ($icon ?? 'times'), 'icon') }}
    {{ $slot }}
</a>
