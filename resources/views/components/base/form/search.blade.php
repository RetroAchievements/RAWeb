<?php

use Illuminate\Support\Str;
?>

@props([
    'autofocus' => false,
    'disabled' => false,
    'fullWidth' => true,
    'help' => null,
    'icon' => false,
    'id' => null,
    'inline' => false,
    'label' => null,
    'loading' => false,
    'maxlength' => 255,
    'model' => null,
    'name' => 'search',
    'placeholder' => false,
    'readonly' => false,
    'required' => false,
    'requiredSilent' => false,
    'showButton' => false,
    'type' => 'search',
    'value' => '',
])

<?php
$id = $id ?: 'input_' . Str::random();
?>

<x-base.form-field
    :help="$help"
    :icon="$icon"
    :id="$id"
    :inline="$inline"
    :label="$label"
    :model="$model"
    :name="$name"
>
    <x-slot name="prepend">
        <label for="{{ $id }}">
            @if($loading)
                <span wire:loading wire:target="search"><x-loader size="xs" /></span>
                <x-fas-search wire:loading.remove wire:target="search" />
            @else
                <x-fas-search />
            @endif
            <span class="sr-only">Search</span>
        </label>
    </x-slot>
    <input
        autocomplete="off"
        class="form-control {{ $fullWidth ? 'w-full' : '' }} {{ $name && $errors && $errors->has($name) ? 'is-invalid' : '' }}"
        id="{{ $id }}"
        maxlength="{{ $maxlength }}"
        name="{{ $name }}"
        placeholder="{{ __('Search') }}&hellip;"
        type="{{ $type }}"
        wire:model.live.debounce.500ms="search"
        {{ $autofocus ? 'autofocus' : '' }}
    >
    @if($showButton)
        <span class="input-group-append">
            <button class="btn btn-link">{{ __('Search') }}</button>
        </span>
    @endif
</x-base.form-field>
