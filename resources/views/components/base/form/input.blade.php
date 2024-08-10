<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
?>

@props([
    'disabled' => false,
    'fullWidth' => true,
    'help' => null,
    'icon' => false,
    'id' => null,
    'inline' => false,
    'isLabelVisible' => true,
    'label' => null,
    'maxlength' => 255,
    'model' => null,
    'name' => null,
    'placeholder' => false,
    'readonly' => false,
    'required' => false,
    'requiredSilent' => false,
    'type' => 'text',
    'value' => '',
])

<?php
if ($model && !$model instanceof Model) {
    throw new Exception('"model" is not an Eloquent model');
}

$id = $id ?: 'input_' . Str::random();
?>

<x-base.form-field
    :help="$help"
    :icon="$icon"
    :id="$id"
    :inline="$inline"
    :inputType="$type"
    :isLabelVisible="$isLabelVisible"
    :label="$label"
    :model="$model"
    :name="$name"
>
    <input
        autocomplete="off"
        class="form-control {{ $fullWidth ? 'w-full' : '' }} {{ $name && $errors && $errors->has($name) ? 'is-invalid' : '' }}"
        id="{{ $id }}"
        maxlength="{{ $maxlength }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $name ? old($name, $model?->getAttribute($name) ?? $value) : $value }}"
        aria-describedby="{{ $name && $errors && $errors->has($name) ? 'error-' . $id : ($help ? 'help-' . $id : '') }}"
        @if($placeholder)placeholder="{{ $placeholder === true ? __('validation.attributes.' . strtolower($name)) : $placeholder }}"@endif
        {{ $disabled ? 'disabled' : '' }}
        {{ $readonly ? 'readonly' : '' }}
        {{ ($required || $requiredSilent) ? 'required' : '' }}
        {{ $attributes }}
    >
</x-base.form-field>
