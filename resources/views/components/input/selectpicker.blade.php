<?php

use Illuminate\Support\Arr;

$optionLabelAttribute ??= 'name';
$options ??= [];
$selected = Arr::wrap($selected) ?? [];

$displayAttribute = str_replace('[]', '', $attribute);

$model->getAttribute($attribute);
?>
<div class="flex row {{ $errors->has($attribute) ? 'has-error' : '' }}">
    <label class="col-form-label lg:col-3 lg:pr-0 lg:text-right whitespace-nowrap {{ $errors && $errors->has($attribute) ? 'text-danger' : '' }}"
           for="{{ $attribute }}">
        {{ __('validation.attributes.'.$displayAttribute) }} {{ !empty($required) ? '*' : '' }}
    </label>
    <div class="lg:col-9">
        @if($options->count() == 1 && !empty($required))
            @php $option = $options->first() @endphp
            <div class="form-control form-control-static">
                {{ $option->optionTitle }}
            </div>
            <input type="hidden" id="{{ $attribute }}" name="{{ $attribute }}" value="{{ $option->id }}">
        @elseif($options->count() > 1 || empty($required))
            <select class="form-control selectpicker" id="{{ $attribute }}" name="{{ $attribute }}"
                    multiple
                    style="height: {{ $options->count() * 20 +20 }}px"
                    data-actions-box="false"
                    data-live-search="false"
                    data-selected-text-format="count > 10"
                    title="{{ __('validation.attributes.'.$displayAttribute) }}">
                @foreach($options as $value => $label)
                    <option value="{{ $value }}" {{ in_array($value, $selected) ? 'selected' : '' }}>
                        {{--value="{{ $option->id }}" {{ old($attribute, !empty($model) && $model->getAttribute($attribute) ? $model->getAttribute($attribute) : null) == $option->id ? 'selected' : '' }}--}}
                        {{ $label }}
                    </option>
                @endforeach
            </select>
        @endif
        @if(!empty($help))
            <p class="help-block text-secondary mb-0">
                {!! $help  !!}
            </p>
        @endif
        @if($errors && $errors->has($displayAttribute))
            <p class="help-block text-danger mb-0">
                <x-fas-exclamation-triangle /> {{ $errors->first($displayAttribute) }}
            </p>
        @endif
    </div>
</div>
