<button class="btn whitespace-nowrap {{ $class ?? '' }}">
    {{ svg('fas-' . ($icon ?? 'save'), 'icon') }}
    {{ (string)$slot ?: __('Save') }}
</button>
