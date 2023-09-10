<div class="flex {{ $errors->has('password') ? 'has-error' : '' }}">
    <label class="col-form-label lg:col-3 lg:pr-0" for="password">
        @if($errors->has('password'))
            <x-fas-times-circle-o /> {{ $errors->first('password') }}
        @else
            {{ __('validation.attributes.password') }} {{ !empty($required) ? '*' : '' }}
        @endif
    </label>
    <div class="input-group">
        <span class="input-group-addon">
            <x-fas-lock />
        </span>
        <input type="password" class="form-control" name="password"
               id="password"
               placeholder="{{ __('validation.attributes.password') }}"
               value="{{ old('password') }}">
    </div>
</div>
<div class="flex {{ $errors->has('password_confirmation') ? 'has-error' : '' }}">
    <label class="col-form-label lg:col-3 lg:pr-0" for="password_confirmation">
        @if($errors->has('password_confirmation'))
            <x-fas-times-circle-o /> {{ $errors->first('password_confirmation') }}
        @else
            {{ __('validation.attributes.password_confirmation') }}*
        @endif
    </label>
    <div class="input-group">
        <span class="input-group-addon">
            <x-fas-lock />
        </span>
        <input type="password" class="form-control"
               name="password_confirmation" id="password_confirmation"
               placeholder="{{ __('validation.attributes.password_confirmation') }}"
               value="{{ old('password_confirmation') }}">
    </div>
</div>
