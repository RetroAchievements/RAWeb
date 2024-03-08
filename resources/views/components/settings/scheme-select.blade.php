<?php
$schemes = [
    // 'system' => __('System Default'), // not needed - detected initially and listened for
    '' => __('Dark'),
    'black' => __('Black'),
    'light' => __('Light'),
    'auto' => __('Auto'),
];
$currentScheme = request()->cookie('scheme') ?? 'system';
$sm = !isset($sm) || $sm;
$id = uniqid();
?>
<label for="scheme-select{{ $id }}" class="sr-only">{{ __('settings.scheme') }}</label>
<select data-choose-scheme class="form-control {{ $sm ? 'form-control-sm' : '' }} scheme-select"
        id="scheme-select{{ $id }}"
>
    @foreach ($schemes as $scheme => $label)
        <option value="{{ $scheme }}" {{ $currentScheme == $scheme ? 'selected' : '' }}>
            {{ __($label) }}
        </option>
    @endforeach
</select>
