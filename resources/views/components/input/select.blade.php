<x-form-field
    :model="$model ?? null"
    :attribute="$attribute"
    :help="$help ?? null"
>
    <x-slot name="label">
        @if($errors->has($attribute))
            <x-far-times-circle /> {{ $errors->first($attribute) }}
        @else
            {{ __('validation.attributes.'.$attribute) }} {{ !empty($required) ? '*' : '' }}
        @endif
    </x-slot>
    @if($options->count() == 1 && !empty($required))
        <?php
        $value = $options->keys()->first();
        $label = $options->first();
        ?>
        <div class="form-control form-control-static">
            {{ $label }}
        </div>
        <input type="hidden" id="{{ $attribute }}" name="{{ $attribute }}" value="{{ $value }}">
    @elseif($options->count() > 1 || empty($required))
        <select class="form-control" id="{{ $attribute }}" name="{{ $attribute }}">
            @if(empty($required))
                <option value="">-</option>
            @endif
            @foreach($options as $value => $label)
                <option
                    value="{{ $value }}" {{ old($attribute, !empty($model) && $model->getAttribute($attribute) ?$model->getAttribute($attribute) : null) == $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    @endif
</x-form-field>
