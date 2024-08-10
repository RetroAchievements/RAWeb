<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
?>

@props([
    'disabled' => false,
    'formActions' => null, // slot
    'fullWidth' => true,
    'errors' => null,
    'help' => null,
    'id' => null,
    'inline' => false,
    'isLabelVisible' => true,
    'label' => null,
    'maxlength' => 2000,
    'model' => null,
    'name' => null,
    'placeholder' => false,
    'readonly' => false,
    'required' => false,
    'requiredSilent' => false,
    'richText' => false,
    'rows' => 10,
    'value' => '',
])

<?php
if ($model && !$model instanceof Model) {
    throw new Exception('"model" is not an Eloquent model');
}

$hasProvidedId = $id !== null;

$id = $id ?: 'input_' . Str::random();
?>

<x-base.form-field
    :help="false"
    :id="$id"
    :inline="$inline"
    :isLabelVisible="$isLabelVisible"
    :label="$label"
    :name="$name"
>
    @if($richText)
        <div class="mb-1">
            <x-base.form.textarea-rich-text-controls :id="$id" />
        </div>
    @endif

    <script>
        const validate_{{ $id }} = function(value) {
            const length = window.getStringByteCount(value);

            return length > 0 && length <= {{ $maxlength }};
        }
    </script>

    <textarea
        {{ $attributes->class([
            'form-control',
            $fullWidth ? 'w-full' : '',
            $name && $errors && $errors->has($name) ? 'is-invalid' : ''
        ]) }}
        id="{{ $id }}"
        maxlength="{{ $maxlength }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        x-on:input="autoExpandTextInput($el); isValid = validate_{{ $id }}($event.target.value);"
        @if($placeholder)placeholder="{{ $placeholder === true ? __('validation.attributes.' . strtolower($name)) : $placeholder }}"@endif
        {{ $disabled ? 'disabled' : '' }}
        {{ $readonly ? 'readonly' : '' }}
        {{ ($required || $requiredSilent) ? 'required' : '' }}
        aria-describedby="{{ $name && $errors && $errors->has($name) ? 'error-' . $id : ($help ? 'help-' . $id : '') }}"
    >{{ $name ? old($name, $model?->getAttribute($name) ?? $value) : $value }}</textarea>

    <div class="help-block text-muted flex justify-between items-center">
        <div>
            @if($maxlength)
                <span class="textarea-counter" data-textarea-id="{{ $id }}">0 / {{ $maxlength }}</span>
            @endif
            @if($help)
                <span class="ml-3">{{ $help }}</span>
            @endif
        </div>

        <div class="flex items-center gap-x-1">
            <div>
                @if ($richText)
                    <x-fas-spinner
                        id="preview-loading-icon"
                        class="opacity-0 transition-all duration-200"
                        aria-hidden="true"
                    />

                    <button
                        type="button"
                        class="btn"
                        onclick="window.loadPostPreview('{{ $id }}', 'post-preview-{{ $id }}')"
                        :disabled="!isValid || isSending"
                    >
                        {{ __('Preview') }}
                    </button>
                @endif
            </div>

            @if ($formActions)
                {{ $formActions }}
            @endif
        </div>
    </div>

    @if ($richText && !$hasProvidedId)
        <div id="post-preview-{{ $id }}"></div>
    @endif
</x-base.form-field>
