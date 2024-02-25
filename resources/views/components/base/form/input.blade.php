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
    'label' => true,
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

<x-form-field
    :help="$help"
    :hidden="$type === 'hidden'"
    :icon="$icon"
    :id="$id"
    :inline="$inline"
    :model="$model"
    :name="$name"
>
    @if($label && $type !== 'hidden')
        <x-slot name="label">
            {{ $label === true ? __('validation.attributes.' . strtolower($name)) : $label }} {{ $required && !$requiredSilent ? '*' : '' }}
        </x-slot>
    @endif

    <input
        autocomplete="off"
        class="form-control {{ $fullWidth ? 'w-full' : '' }} {{ $name && $errors && $errors->has($name) ? 'is-invalid' : '' }}"
        id="{{ $id }}"
        maxlength="{{ $maxlength }}"
        name="{{ $name }}"
        type="{{ $type }}"
        value="{{ $name ? old($name, $model?->getAttribute($name) ?? $value) : $value }}"
        @if($name && $errors && $errors->has($name))aria-describedby="error-{{ $id }}"@endif
        @if($placeholder)placeholder="{{ $placeholder === true ? __('validation.attributes.' . strtolower($name)) : $placeholder }}"@endif
        {{ $disabled ? 'disabled' : '' }}
        {{ $readonly ? 'readonly' : '' }}
        {{ ($required || $requiredSilent) ? 'required' : '' }}
    >
</x-form-field>
