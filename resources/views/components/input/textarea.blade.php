<x-form-field
    :attribute="$attribute ?? null"
    :fieldId="$fieldId ?? null"
    :fullWidth="$fullWidth ?? false"
    :help="$help ?? null"
    :label="$label ?? true"
>
    @if($label ?? true)
        <x-slot name="label">
            {{ __('validation.attributes.'.strtolower($attribute)) }} {{ !empty($required) ? '*' : '' }}
        </x-slot>
    @endif
    @if($shortcode ?? false)
        <div class="mb-1">
            <x-input.shortcode :id="$id ?? $attribute" />
        </div>
    @endif
    {{--<div x-data="alpineData()" data-limit="100">--}}
    <div data-limit="100">
        <textarea class="form-control {{ $class ?? '' }} {{ $errors->has($attribute) ? 'is-invalid' : '' }}"
                  x-model="content"
                  rows="{{ $rows ?? 5 }}"
                  maxlength="{{ $maxlength ?? PHP_INT_MAX }}"
                  id="{{ $id ?? $attribute }}" name="{{ $name ?? $attribute }}"
                  placeholder="{{ $placeholder ?? __('validation.attributes.'.$attribute) }}"
        >{{ old($attribute, !empty($model) ? $model->getAttribute($attribute) : null) }}</textarea>
        {{--<p id="remaining">
            You have <span x-text="remaining"></span> characters remaining.
        </p>--}}
    </div>
    {{--<script>
        function alpineData() {
            return {
                content: '',
                limit: $el.dataset.limit,
                get remaining() {
                    return this.limit - this.content.length
                }
            }
        }
    </script>--}}
</x-form-field>
