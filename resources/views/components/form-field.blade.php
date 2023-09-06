<div class="{{ ($inline ?? false) ? 'lg:flex' : '' }} {{ ($fullWidth ?? false) ? '' : '' }} mb-2">
    @if($label ?? false)
        <div class="{{ ($fullWidth ?? false) ? '' : (($inline ?? false) ? 'lg:w-36 lg:pr-3 lg:text-right' : '') }} pt-1 whitespace-nowrap {{ $errors && $errors->has($attribute ?? null) ? 'text-danger' : '' }}">
            <label for="{{ $fieldId ?? $attribute ?? null }}">
                {{ $label }} {{ !empty($required) ? '*' : '' }}
            </label>
        </div>
    @endif
    <div class="grow">
        @if(!empty($icon))
            <div class="input-group">
                <span class="input-group-prepend">
                    <span class="input-group-text">
                        {{ svg('fas-'.$icon, 'icon') }}
                    </span>
                </span>
                {{ $slot }}
            </div>
        @else
            {{ $slot }}
        @endif
        @if(!empty($help))
            <p class="help-block text-secondary mb-0">
                {!! $help  !!}
            </p>
        @endif
        @if($attribute ?? null)
            @error($attribute)
            <p class="help-block text-danger mb-0" id="error-{{ $fieldId ?? $attribute }}">
                <x-fas-exclamation-triangle /> {{ $errors->first($attribute) }}
            </p>
            @enderror
        @endif
    </div>
</div>
