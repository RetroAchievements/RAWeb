<button
    {{ $attributes->class([
        'btn whitespace-nowrap',
    ]) }}
    :disabled="!isValid || isSending"
>
    {{ svg('fas-' . ($icon ?? 'paper-plane'), 'icon') }}
    <span x-show="isSending" x-cloak>{{ __('Sending...') }}</span>
    <span x-show="!isSending">{{ (string) $slot ?: __('Submit') }}</span>
</button>
