<x-form-field
    :model="$model ?? null"
    :attribute="$attribute ?? null"
    :fieldId="$fieldId ?? null"
    :help="$help ?? null"
>
    {{--<code>x-input</code>--}}
    <x-slot name="label">
        {{ $label ?? __('validation.attributes.'.$attribute) }} {{ !empty($required) ? '*' : '' }}
    </x-slot>
    {{--wire:dirty.class="border-info" wire:model.lazy="{{ $attribute }}"--}}
    <input autocomplete="off"
           type="{{ $type ?? 'text' }}" id="{{ $fieldId ?? $attribute }}" name="{{ $attribute }}"
           class="form-control {{ $errors && $errors->has($attribute) ? 'is-invalid' : '' }}"
           {{ !empty($disabled) ? 'disabled' : '' }} {{ !empty($readonly) ? 'readonly' : '' }}
           {{ !empty($required) ? 'required' : '' }}
           placeholder="{{ $placeholder ?? __('validation.attributes.'.$attribute) }}"
           value="{{ old($attribute, !empty($model) ? $model->getAttribute($attribute) : null)}}">
</x-form-field>
