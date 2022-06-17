<?php
$colorThemes = [
    // original themes
    '' => __('Default'),
    'red' => __('Red'),
    'green' => __('Green'),
    'blue' => __('Blue'),
    'orange' => __('Orange'),
    'pink' => __('Pink'),
    // 'black' => __('Black'),
];
$sm = !isset($sm) || $sm;
$id = uniqid();
?>
<label for="theme-select{{ $id }}" class="sr-only">{{ __('Theme') }}</label>
<select data-choose-theme class="select select-bordered {{ $sm ? 'h-auto min-h-0 py-0' : '' }}"
        id="theme-select{{ $id }}"
>
    @foreach ($colorThemes as $theme => $label)
        <option value="{{ $theme }}">{{ __($label) }}</option>
    @endforeach
</select>
