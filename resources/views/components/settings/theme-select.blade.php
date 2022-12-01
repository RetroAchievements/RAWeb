<?php
$colorThemes = [
    // original themes
    '' => __('Default'),
    'slate' => __('Slate'),
    'stone' => __('Stone'),
    'red' => __('Red'),
    'orange' => __('Orange'),
    'amber' => __('Amber'),
    'yellow' => __('Yellow'),
    'lime' => __('Lime'),
    'green' => __('Green'),
    'emerald' => __('Emerald'),
    'teal' => __('Teal'),
    'cyan' => __('Cyan'),
    'sky' => __('Sky'),
    'blue' => __('Blue'),
    'indigo' => __('Indigo'),
    'violet' => __('Violet'),
    'purple' => __('Purple'),
    'fuchsia' => __('Fuchsia'),
    'pink' => __('Pink'),
    'rose' => __('Rose'),
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
