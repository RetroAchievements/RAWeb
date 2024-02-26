<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
?>

@props([
    'disabled' => false,
    'fullWidth' => false,
    'help' => null,
    'id' => null,
    'inline' => false,
    'label' => true,
    'model' => null,
    'name' => 'username',
    'placeholder' => false,
    'readonly' => false,
    'required' => false,
    'requiredSilent' => false,
    'value' => '',
])

<?php
if ($model && !$model instanceof Model) {
    throw new Exception('"model" is not an Eloquent model');
}

$id = $id ?: 'input_' . Str::random();

$value = $name ? old($name, $model?->getAttribute($name) ?? $value) : $value;
$username = $value ?: '_User';
$imageSource = media_asset("/UserPic/$username.png");
?>

<script>
function onUserChange(fieldId, imageId) {
    const fieldValue = $('#' + fieldId).val();
    if (fieldValue.length > 2) {
        const fieldIcon = $('#' + imageId);
        if (fieldIcon) {
            fieldIcon.attr('src', mediaAsset('/UserPic/' + fieldValue + '.png'));
        }
    }
}

$(function () {
    const $searchUser = $('#{{ $id }}');

    $searchUser.autocomplete({
        source: function (request, response) {
          request.source = 'user';
          $.post('/request/search.php', request)
            .done(function (data) {
              response(data);
            });
        },
        minLength: 2
    });

    $searchUser.autocomplete({
        select: function (event, ui) {
          var TABKEY = 9;
          if (event.keyCode === TABKEY) {
            $('.searchusericon').attr('src', mediaAsset('/UserPic/' + ui.item.label + '.png'));
          }
        },
    });

    $searchUser.on('autocompleteselect', function (event, ui) {
        $searchUser.val(ui.item.label);
        $('.searchusericon').attr('src', mediaAsset('/UserPic/' + ui.item.label + '.png'));
    });
});
</script>

<x-form-field
    :help="$help"
    :id="$id"
    :inline="$inline"
    :name="$name"
>
    @if($label)
        <x-slot name="label">
            {{ $label === true ? __('validation.attributes.' . strtolower($name)) : $label }} {{ $required && !$requiredSilent ? '*' : '' }}
        </x-slot>
    @endif
    <div class="w-full flex gap-2 justify-start items-center">
        <img
            id="select-user-avatar-{{ $id }}"
            src="{{ $imageSource }}"
            width="24"
            height="24"
        />
        <input
            autocomplete="off"
            class="form-control {{ $fullWidth ? 'w-full' : '' }} {{ $name && $errors && $errors->has($name) ? 'is-invalid' : '' }}"
            id="{{ $id }}"
            maxlength="20"
            name="{{ $name }}"
            onblur="onUserChange('{{ $id }}', 'select-user-avatar-{{ $id }}'); return false;"
            type="text"
            value="{{ $name ? old($name, $model?->getAttribute($name) ?? $value) : $value }}"
            aria-describedby="{{ $name && $errors && $errors->has($name) ? 'error-' . $id : ($help ? 'help-' . $id : '') }}"
            @if($placeholder)placeholder="{{ $placeholder === true ? __('validation.attributes.' . strtolower($name)) : $placeholder }}"@endif
            {{ $disabled ? 'disabled' : '' }}
            {{ $readonly ? 'readonly' : '' }}
            {{ $required ? 'required' : '' }}
        >
    </div>
</x-form-field>
