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

<x-form-field
    :help="$help"
    :id="$id"
    :inline="$inline"
    :model="$model"
    :name="$name"
>
    <div class="flex gap-2 items-center {{ $inline ? 'lg:ml-36' : '' }}">
        <input
            class="form-control {{ $name && $errors && $errors->has($name) ? 'is-invalid' : '' }}"
            id="{{ $id }}"
            name="{{ $name }}"
            type="checkbox"
            value="1"
            {{ $name ? (old($name, $model?->getAttribute($name) ?? $checked) ? 'checked' : '') : ($checked ? 'checked' : '') }}
            @if($name && $errors && $errors->has($name))aria-describedby="error-{{ $id }}"@endif
            {{ $disabled ? 'disabled' : '' }}
            {{ ($required || $requiredSilent) ? 'required' : '' }}
        >
        <label for="{{ $id }}">
            {{ $label ?? __('validation.attributes.'.$name) }} {{ $required && !$requiredSilent ? '*' : '' }}
        </label>
    </div>
</x-form-field>
