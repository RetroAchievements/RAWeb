@props([
    'hasRequiredFields' => false,
    'inline' => false,
    'submitLabel' => __('Submit'),
    'largeSubmit' => false,
])

<div class="flex {{ $largeSubmit ? 'flex-wrap' : '' }} items-center {{ $inline ? 'lg:ml-36' : '' }}">
    <x-base.button.submit class="{{ $largeSubmit ? 'w-full text-center py-2' : '' }}">
        {{ $submitLabel }}
    </x-base.button.submit>

    @if ($hasRequiredFields)
        <div class="{{ $largeSubmit ? 'mt-2' : 'ml-3' }} whitespace-nowrap">
            * {{ __('Required') }}
        </div>
    @endif

    <div class="ml-auto">
        {{ $slot }}
    </div>
</div>
