<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
?>

@props([
    'checked' => false,
    'disabled' => false,
    'help' => null,
    'id' => null,
    'inline' => false,
    'isLabelVisible' => true,
    'label' => null,
    'model' => null,
    'name' => null,
    'required' => false,
    'requiredSilent' => false,
])

<?php
if ($model) {
    assert($model instanceof Model);
}

$id = $id ?: 'input_' . Str::random();
?>

<x-base.form-field
    :help="$help"
    :id="$id"
    :inline="$inline"
    :isLabelVisible="$isLabelVisible"
    inputType="checkbox"
    :model="$model"
    :name="$name"
>
    <div class="{{ $inline ? 'lg:ml-36' : '' }}">
        <label for="{{ $id }}" class="inline-flex gap-2 items-center">
            <input
                class="form-control {{ $name && $errors && $errors->has($name) ? 'is-invalid' : '' }}"
                id="{{ $id }}"
                name="{{ $name }}"
                type="checkbox"
                value="1"
                {{ $name ? (old($name, $model?->getAttribute($name) ?? $checked) ? 'checked' : '') : ($checked ? 'checked' : '') }}
                aria-describedby="{{ $name && $errors && $errors->has($name) ? 'error-' . $id : ($help ? 'help-' . $id : '') }}"
                {{ $disabled ? 'disabled' : '' }}
                {{ ($required || $requiredSilent) ? 'required' : '' }}
            >
            <span>
                {!! trim($slot) ?: $label ?? __('validation.attributes.'.$name) !!} {{ $required && !$requiredSilent ? '*' : '' }}
            </span>
        </label>
    </div>
</x-base.form-field>
