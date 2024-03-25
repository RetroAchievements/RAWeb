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
    'isLabelVisible' => true,
    'label' => null,
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

<x-base.form-field
    :help="$help ?? null"
    :id="$id"
    :inline="$inline"
    :isLabelVisible="$isLabelVisible"
    :label="$label"
    :model="$model"
    :name="$name"
>
    @if($options->count() == 1 && $required)
        <input
            class="form-control {{ $fullWidth ? 'w-full' : '' }} {{ $name && $errors && $errors->has($name) ? 'is-invalid' : '' }}"
            id="{{ $id }}"
            name="{{ $name }}"
            type="text"
            value="{{ $options->first() }}"
            aria-describedby="{{ $name && $errors && $errors->has($name) ? 'error-' . $id : ($help ? 'help-' . $id : '') }}"
            readonly
        >
    @elseif($options->count() > 1 || !$required)
        <select
            class="form-control {{ $fullWidth ? 'w-full' : '' }} {{ $name && $errors && $errors->has($name) ? 'is-invalid' : '' }}"
            id="{{ $id }}"
            name="{{ $name }}"
            aria-describedby="{{ $name && $errors && $errors->has($name) ? 'error-' . $id : ($help ? 'help-' . $id : '') }}"
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
</x-base.form-field>
