@props([
    'help' => null,
    'hidden' => null,
    'icon' => null,
    'id' => null,
    'inline' => false,
    'label' => false,
    'name' => null,
    'prepend' => null,
    'required' => false,
])

<div class="{{ $inline ? 'lg:flex' : '' }} {{ $hidden && !($name && $errors && $errors->has($name)) ? '' : 'mb-3 last-of-type:mb-0' }}">
    @if($label)
        <label for="{{ $id ?? $name }}" class="block {{ $inline ? 'lg:w-36 lg:pr-3 lg:text-right lg:pt-1 whitespace-nowrap' : 'mb-1' }} {{ $name && $errors && $errors->has($name) ? 'text-danger' : '' }}">
            {{ $label }} {{ $required ? '*' : '' }}
        </label>
    @endif
    <div class="grow">
        @if($icon || $prepend)
            <div class="flex flex-row">
                <div class="form-control-prepend flex items-center">
                    {{ $icon ? svg('fas-' . $icon, 'icon') : $prepend }}
                </div>
                {{ $slot }}
            </div>
        @else
            {{ $slot }}
        @endif
        @if($help)
            <p class="help-block text-muted">
                {!! $help  !!}
            </p>
        @endif
        @if($name)
            @error($name)
            <p class="help-block text-danger" id="error-{{ $id ?? $name }}">
                <x-fas-exclamation-triangle /> {{ $errors->first($name) }}
            </p>
            @enderror
        @endif
    </div>
</div>
