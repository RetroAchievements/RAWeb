<x-form-field
    :model="$model ?? null"
    :attribute="$attribute ?? null"
    :fieldId="$fieldId ?? null"
    :help="$help ?? null"
    :inline="$inline ?? false"
>
    <x-slot name="label">
        {{ $label ?? __('validation.attributes.'.strtolower($attribute)) }} {{ !empty($required) ? '*' : '' }}
    </x-slot>
    <input autocomplete="off"
        type="{{ $type ?? 'text' }}" id="{{ $fieldId ?? $attribute }}" name="{{ $attribute }}"
        class="form-control {{ $errors && $errors->has($attribute) ? 'is-invalid' : '' }}"
        {{ !empty($disabled) ? 'disabled' : '' }} {{ !empty($readonly) ? 'readonly' : '' }}
        {{ !empty($required) ? 'required' : '' }}
        value="{{ old($attribute, !empty($model) ? $model->getAttribute($attribute) : null)}}"
        @if($errors && $errors->has($attribute))
        aria-describedby="error-{{ $fieldId ?? $attribute }}"
        @endif
        @if($placeholder ?? false)
        placeholder="{{ $placeholder === true ? __('validation.attributes.'.strtolower($attribute)) : $placeholder }}"
        @endif
    >
</x-form-field>
