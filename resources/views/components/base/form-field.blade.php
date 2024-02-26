@props([
    'help' => null,
    'icon' => null,
    'id' => null,
    'inline' => false,
    'inputType' => null,
    'label' => null,
    'name' => null,
    'prepend' => null,
    'required' => false,
])

<?php
$id = $id ?: 'input_' . Str::random();
?>

<div class="{{ $inline ? 'lg:flex' : '' }} {{ $inputType === 'hidden' && !($name && $errors && $errors->has($name)) ? '' : 'mb-3 last-of-type:mb-0' }}">
    @if($inputType !== 'hidden' && $inputType !== 'checkbox')
        <label for="{{ $id }}" class="block {{ $inline ? 'lg:w-36 lg:pr-3 lg:text-right lg:pt-1' : 'mb-1' }} {{ $name && $errors && $errors->has($name) ? 'text-danger' : '' }}">
            {{ $label ?: __('validation.attributes.' . strtolower($name)) }} {{ $required && !$requiredSilent ? '*' : '' }}
        </label>
    @endif
    <div class="grow">
        @if($icon || $prepend)
            <div class="flex flex-row">
                <div class="form-control-prepend flex items-center" aria-hidden="true">
                    {{ $icon ? svg('fas-' . $icon, 'icon') : $prepend }}
                </div>
                {{ $slot }}
            </div>
        @else
            {{ $slot }}
        @endif
        @if($help)
            <p class="help-block text-muted" id="help-{{ $id }}">
                {!! $help  !!}
            </p>
        @endif
        @if($name)
            @error($name)
            <p class="help-block text-danger" id="error-{{ $id }}">
                <x-fas-exclamation-triangle /> {{ $errors->first($name) }}
            </p>
            @enderror
        @endif
    </div>
</div>
