<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
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
    'options' => collect(),
    'model' => null,
    'name' => null,
    'placeholder' => false,
    'readonly' => false,
    'required' => false,
    'requiredSilent' => false,
    'value' => null,
])

<?php
if ($model && !$model instanceof Model) {
    throw new Exception('"model" property is not an Eloquent model');
}

$options = !$options instanceof Collection ? collect($options) : $options;

$id = $id ?: 'input_' . Str::random();
?>

<x-form-field
    :model="$model ?? null"
    :name="$name"
    :help="$help ?? null"
>
    @if($label)
        <x-slot name="label">
            {{ $label === true ? __('validation.attributes.' . strtolower($name)) : $label }} {{ $required && !$requiredSilent ? '*' : '' }}
        </x-slot>
    @endif

    @if($options->count() == 1 && $required)
        <input
            class="form-control {{ $fullWidth ? 'w-full' : '' }} {{ $name && $errors && $errors->has($name) ? 'is-invalid' : '' }}"
            id="{{ $id }}"
            name="{{ $name }}"
            type="text"
            value="{{ $options->first() }}"
            @if($name && $errors && $errors->has($name))aria-describedby="error-{{ $id }}"@endif
            readonly
        >
    @elseif($options->count() > 1 || !$required)
        <select
            class="form-control {{ $fullWidth ? 'w-full' : '' }} {{ $name && $errors && $errors->has($name) ? 'is-invalid' : '' }}"
            id="{{ $id }}"
            name="{{ $name }}"
            @if($name && $errors && $errors->has($name))aria-describedby="error-{{ $id }}"@endif
        >
            @if(empty($required))
                <option value="">-</option>
            @endif
            @foreach($options as $optionValue => $optionLabel)
                <option
                    value="{{ $optionValue }}" {{ (string) ($name ? old($name, $model?->getAttribute($name) ?? $value) : $value) === (string) $optionValue ? 'selected' : '' }}>
                    {{ $optionLabel }}
                </option>
            @endforeach
        </select>
    @endif
</x-form-field>
