<div class="flex {{ $errors->has('status') ? 'has-error' : '' }}">
    <label class="col-form-label lg:col-3 lg:pr-0" for="status">
        @if($errors->has('status'))
            <x-fas-times-circle-o /> {{ $errors->first('status') }}
        @else
            {{ __('validation.attributes.status') }} {{ !empty($required) ? '*' : '' }}
        @endif
    </label>
    <select class="form-control" id="status" name="status">';
        <option value="">Status</option>
        <option value="public" {{ old('status', $event->status ?? null) == 'public' ? 'selected' : '' }}>
            Public
        </option>
        <option value="private" {{ old('status', $event->status ?? null) == 'private' ? 'selected' : '' }}>
            Private
        </option>
    </select>
</div>
