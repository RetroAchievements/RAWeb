<div class="flex {{ $errors->has($attribute) ? 'has-error' : '' }}">
    <label class="col-form-label lg:col-3 lg:pr-0" for="{{ $attribute }}">
        @if($errors->has($attribute))
            <x-fas-times-circle-o /> {{ $errors->first($attribute) }}
        @else
            {{ __('validation.attributes.'.$attribute) }} {{ !empty($required) ? '*' : '' }}
        @endif
    </label>
    <div class="input-group">
        <div class="input-group-addon">
            <x-fas-calendar />
        </div>
        <input class="form-control float-right datetimepicker" id="{{ $attribute }}" name="{{ $attribute }}"
               placeholder="{{ $placeholder ?? __('validation.attributes.'.$attribute) }}"
               value="{{ old($attribute, isset($model) ? $model->getAttribute($attribute)->format('j M Y G:i') : null) }}">
    </div>
    @if(!empty($help))
        <p class="help-block text-secondary mb-0">
            {{ $help }}
        </p>
    @endif
</div>
